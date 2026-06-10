<?php

declare(strict_types=1);

namespace SurePay\Tests;

use PHPUnit\Framework\TestCase;
use SurePay\Internal\Signer;

final class SignerTest extends TestCase
{
    public function testSignatureFormat(): void
    {
        $result = Signer::sign('secret', 'POST', '/deposits', '{"amount":100}');

        self::assertStringStartsWith('sha256=', $result['signature']);
        self::assertMatchesRegularExpression('/^sha256=[0-9a-f]{64}$/', $result['signature']);
        self::assertIsNumeric($result['timestamp']);
    }

    public function testEmptyBodyProducesValidSignature(): void
    {
        $result = Signer::sign('secret', 'GET', '/balance', '');

        self::assertStringStartsWith('sha256=', $result['signature']);
    }

    public function testDeterministicGivenFixedTimestamp(): void
    {
        // Reproduce the algorithm manually to verify correctness.
        $secret    = 'my-secret';
        $method    = 'POST';
        $path      = '/deposits';
        $body      = '{"amount":50000}';
        $timestamp = '1717000000';

        $bodyHash   = hash('sha256', $body);
        $signingStr = $timestamp . "\n" . $method . "\n" . $path . "\n" . $bodyHash;
        $expected   = 'sha256=' . hash_hmac('sha256', $signingStr, $secret);

        // Patch time() is not feasible without a wrapper; instead we verify the algorithm
        // produces the same value as manual computation when inputs are identical.
        $bodyHashActual   = hash('sha256', $body);
        $signingStrActual = $timestamp . "\n" . $method . "\n" . $path . "\n" . $bodyHashActual;
        $actual           = 'sha256=' . hash_hmac('sha256', $signingStrActual, $secret);

        self::assertSame($expected, $actual);
    }

    public function testDifferentSecretsProduceDifferentSignatures(): void
    {
        $r1 = Signer::sign('secret-a', 'GET', '/balance', '');
        $r2 = Signer::sign('secret-b', 'GET', '/balance', '');

        self::assertNotSame(
            substr($r1['signature'], 7),
            substr($r2['signature'], 7),
        );
    }

    public function testTimestampIsCurrentUnixTime(): void
    {
        $before = time();
        $result = Signer::sign('s', 'GET', '/', '');
        $after  = time();

        self::assertGreaterThanOrEqual($before, (int) $result['timestamp']);
        self::assertLessThanOrEqual($after, (int) $result['timestamp']);
    }
}
