<?php

declare(strict_types=1);

namespace SurePay\Internal;

final class Signer
{
    private function __construct() {}

    /**
     * Signs a request using HMAC-SHA256.
     *
     * signing_str = timestamp + "\n" + METHOD + "\n" + path + "\n" + hex(sha256(body))
     * signature   = "sha256=" + hex(hmac-sha256(secret, signing_str))
     *
     * @return array{signature: string, timestamp: string}
     */
    public static function sign(string $secret, string $method, string $path, string $body): array
    {
        $timestamp = (string) time();
        $bodyHash  = hash('sha256', $body);
        $signingStr = $timestamp . "\n" . $method . "\n" . $path . "\n" . $bodyHash;
        $signature  = 'sha256=' . hash_hmac('sha256', $signingStr, $secret);

        return ['signature' => $signature, 'timestamp' => $timestamp];
    }
}
