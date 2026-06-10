<?php

declare(strict_types=1);

namespace SurePay\Service;

final class WebhookVerifier
{
    public function __construct(private readonly string $secret) {}

    /**
     * Verify a webhook using the signature embedded in the `signature` field of the body.
     */
    public function verify(string $body): bool
    {
        return $this->verifyWithSignature($body, null);
    }

    /**
     * Verify a webhook using an explicit signature, e.g. from the X-Surepay-Signature header.
     */
    public function verifyWithSignature(string $body, ?string $sig): bool
    {
        try {
            $payload = json_decode($body, true, 512, JSON_THROW_ON_ERROR);

            if (!is_array($payload)) {
                return false;
            }

            if ($sig === null || $sig === '') {
                $sig = isset($payload['signature']) ? (string) $payload['signature'] : null;
            }

            if ($sig === null || $sig === '') {
                return false;
            }

            unset($payload['signature']);

            $canonical = $this->marshalSorted($payload);
            $computed  = hash_hmac('sha256', $canonical, $this->secret);

            return hash_equals($computed, $sig);
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * @param array<string, mixed> $map
     *
     * Sorts keys alphabetically before serialising — must match the server's canonical form.
     */
    private function marshalSorted(array $map): string
    {
        ksort($map);

        $parts = [];
        foreach ($map as $key => $value) {
            $parts[] = json_encode($key, JSON_THROW_ON_ERROR) . ':' . json_encode($value, JSON_THROW_ON_ERROR);
        }

        return '{' . implode(',', $parts) . '}';
    }
}
