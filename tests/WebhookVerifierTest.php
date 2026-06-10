<?php

declare(strict_types=1);

namespace SurePay\Tests;

use PHPUnit\Framework\TestCase;
use SurePay\Service\WebhookVerifier;

final class WebhookVerifierTest extends TestCase
{
    private const SECRET = 'webhook-secret';

    private function makeBody(array $payload, string $secret): string
    {
        ksort($payload);
        $parts = [];
        foreach ($payload as $key => $value) {
            $parts[] = json_encode($key) . ':' . json_encode($value);
        }
        $canonical = '{' . implode(',', $parts) . '}';
        $sig = hash_hmac('sha256', $canonical, $secret);

        $payload['signature'] = $sig;
        return json_encode($payload);
    }

    public function testVerifyEmbeddedSignaturePass(): void
    {
        $body     = $this->makeBody(['event' => 'deposit.success', 'id' => 'abc123'], self::SECRET);
        $verifier = new WebhookVerifier(self::SECRET);

        self::assertTrue($verifier->verify($body));
    }

    public function testVerifyEmbeddedSignatureFail(): void
    {
        $body     = json_encode(['event' => 'deposit.success', 'id' => 'abc123', 'signature' => 'badsig']);
        $verifier = new WebhookVerifier(self::SECRET);

        self::assertFalse($verifier->verify($body));
    }

    public function testVerifyExplicitSignaturePass(): void
    {
        $payload = ['event' => 'payout.success', 'id' => 'xyz789'];
        ksort($payload);
        $parts = [];
        foreach ($payload as $key => $value) {
            $parts[] = json_encode($key) . ':' . json_encode($value);
        }
        $canonical = '{' . implode(',', $parts) . '}';
        $sig       = hash_hmac('sha256', $canonical, self::SECRET);

        $body     = json_encode($payload);
        $verifier = new WebhookVerifier(self::SECRET);

        self::assertTrue($verifier->verifyWithSignature($body, $sig));
    }

    public function testVerifyExplicitSignatureFail(): void
    {
        $body     = json_encode(['event' => 'deposit.success']);
        $verifier = new WebhookVerifier(self::SECRET);

        self::assertFalse($verifier->verifyWithSignature($body, 'wrongsignature'));
    }

    public function testVerifyMissingSignatureReturnsFalse(): void
    {
        $body     = json_encode(['event' => 'deposit.success']);
        $verifier = new WebhookVerifier(self::SECRET);

        self::assertFalse($verifier->verify($body));
    }

    public function testVerifyInvalidJsonReturnsFalse(): void
    {
        $verifier = new WebhookVerifier(self::SECRET);

        self::assertFalse($verifier->verify('not json at all'));
    }

    public function testVerifyWrongSecretFails(): void
    {
        $body     = $this->makeBody(['event' => 'deposit.success'], self::SECRET);
        $verifier = new WebhookVerifier('other-secret');

        self::assertFalse($verifier->verify($body));
    }

    public function testSortOrderIsAlphabetical(): void
    {
        // Keys "z" and "a" — if sort is wrong the signature will differ from a correctly sorted one.
        $payload = ['z_key' => 'last', 'a_key' => 'first'];
        $body    = $this->makeBody($payload, self::SECRET);

        $verifier = new WebhookVerifier(self::SECRET);
        self::assertTrue($verifier->verify($body));
    }
}
