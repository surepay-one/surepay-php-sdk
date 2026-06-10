<?php

declare(strict_types=1);

namespace SurePay\Internal;

use SurePay\Exception\SurePayException;

final class HttpClient
{
    private readonly string $apiSecret;

    /**
     * @param callable|null $handler Injectable curl handler for testing.
     *                               Signature: fn(string $url, array $options): array{body: string, status: int}
     */
    public function __construct(
        private readonly string $apiKey,
        string $apiSecret,
        private readonly string $baseUrl,
        private readonly int $maxRetries,
        private readonly int $timeoutSeconds,
        private readonly mixed $handler = null,
    ) {
        $this->apiSecret = $apiSecret;
    }

    /**
     * Execute a request that returns a single resource object.
     * Unwraps the `data` envelope and returns an associative array.
     *
     * @return array<string, mixed>
     * @throws SurePayException
     */
    public function execute(
        string $method,
        string $path,
        mixed $body = null,
        ?string $idempotencyKey = null,
    ): array {
        $envelope = $this->executeRaw($method, $path, $body, $idempotencyKey);
        $data     = $envelope['data'] ?? null;

        if ($data === null) {
            return [];
        }

        return (array) $data;
    }

    /**
     * Execute a request that returns a list (`data` is an array).
     * Returns both the items array and the meta object.
     *
     * @return array{items: array<int, array<string, mixed>>, meta: array<string, mixed>}
     * @throws SurePayException
     */
    public function executeList(
        string $method,
        string $path,
        mixed $body = null,
        ?string $idempotencyKey = null,
    ): array {
        $envelope = $this->executeRaw($method, $path, $body, $idempotencyKey);

        $data = $envelope['data'] ?? [];
        $meta = $envelope['meta'] ?? [];

        // Support both array-of-objects and {items, meta} wrapped inside data
        if (is_array($data) && isset($data['items'])) {
            $meta  = $data['meta'] ?? $meta;
            $items = (array) $data['items'];
        } else {
            $items = array_values((array) $data);
        }

        return ['items' => $items, 'meta' => (array) $meta];
    }

    /**
     * @return array<string, mixed>
     * @throws SurePayException
     */
    private function executeRaw(
        string $method,
        string $path,
        mixed $body,
        ?string $idempotencyKey,
    ): array {
        return $this->attempt($method, $path, $body, $idempotencyKey, 0);
    }

    /**
     * @return array<string, mixed>
     * @throws SurePayException
     */
    private function attempt(
        string $method,
        string $path,
        mixed $body,
        ?string $idempotencyKey,
        int $n,
    ): array {
        $bodyBytes = $body !== null ? json_encode($body, JSON_THROW_ON_ERROR) : '';

        $headers = [
            'X-API-Key: ' . $this->apiKey,
            'Accept: application/json',
        ];

        if ($bodyBytes !== '') {
            $headers[] = 'Content-Type: application/json';
        }

        if ($this->apiSecret !== '') {
            $parsed    = parse_url($this->baseUrl . $path);
            $urlPath   = $parsed['path'] ?? $path;
            $signed    = Signer::sign($this->apiSecret, $method, $urlPath, $bodyBytes);
            $headers[] = 'X-Signature: ' . $signed['signature'];
            $headers[] = 'X-Timestamp: ' . $signed['timestamp'];
        }

        if ($idempotencyKey !== null && $idempotencyKey !== '') {
            $headers[] = 'Idempotency-Key: ' . $idempotencyKey;
        }

        try {
            [$responseBody, $status] = $this->send($method, $this->baseUrl . $path, $bodyBytes, $headers);
        } catch (\Throwable $e) {
            if ($n < $this->maxRetries) {
                $this->backoff($n);
                return $this->attempt($method, $path, $body, $idempotencyKey, $n + 1);
            }
            throw new SurePayException(0, 'network_error', $e->getMessage());
        }

        if ($status >= 500 && $n < $this->maxRetries) {
            $this->backoff($n);
            return $this->attempt($method, $path, $body, $idempotencyKey, $n + 1);
        }

        if ($status >= 400) {
            throw $this->parseError($status, $responseBody);
        }

        return json_decode($responseBody, true, 512, JSON_THROW_ON_ERROR) ?? [];
    }

    /**
     * @return array{0: string, 1: int}
     */
    private function send(string $method, string $url, string $body, array $headers): array
    {
        if ($this->handler !== null) {
            $result = ($this->handler)($url, [
                'method'  => $method,
                'body'    => $body,
                'headers' => $headers,
            ]);
            return [$result['body'], $result['status']];
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => $this->timeoutSeconds,
            CURLOPT_CONNECTTIMEOUT => $this->timeoutSeconds,
            CURLOPT_CUSTOMREQUEST  => $method,
        ]);

        if ($body !== '') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        $responseBody = curl_exec($ch);
        $errno        = curl_errno($ch);
        $error        = curl_error($ch);
        $status       = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($errno !== 0) {
            throw new \RuntimeException('cURL error ' . $errno . ': ' . $error);
        }

        return [(string) $responseBody, $status];
    }

    private function parseError(int $status, string $body): SurePayException
    {
        try {
            $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
            $err     = $decoded['error'] ?? $decoded;
            $code    = $err['code'] ?? 'unknown';
            $message = $err['message'] ?? 'Unknown error';
        } catch (\Throwable) {
            $code    = 'unknown';
            $message = $body;
        }

        return new SurePayException($status, $code, $message);
    }

    private function backoff(int $n): void
    {
        // 1s, 2s, 4s — capped to avoid accidental long waits
        $seconds = min(1 << $n, 30);
        sleep($seconds);
    }
}
