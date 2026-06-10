<?php

declare(strict_types=1);

namespace SurePay\Tests;

use PHPUnit\Framework\TestCase;
use SurePay\Exception\SurePayException;
use SurePay\SurePay;

final class BalanceServiceTest extends TestCase
{
    private function makeClient(array $response): SurePay
    {
        $handler = static fn(string $url, array $options): array => $response;

        return SurePay::withHandler('test-key', 'test-secret', $handler);
    }

    public function testGetReturnsBalance(): void
    {
        $client = $this->makeClient([
            'status' => 200,
            'body'   => json_encode([
                'data' => [
                    'balance'   => 1_000_000,
                    'hold'      => 50_000,
                    'available' => 950_000,
                    'currency'  => 'VND',
                ],
            ]),
        ]);

        $balance = $client->balance->get();

        self::assertSame(1_000_000, $balance->balance);
        self::assertSame(50_000, $balance->hold);
        self::assertSame(950_000, $balance->available);
        self::assertSame('VND', $balance->currency);
    }

    public function testGetThrows401OnUnauthorized(): void
    {
        $client = $this->makeClient([
            'status' => 401,
            'body'   => json_encode(['error' => ['code' => 'unauthorized', 'message' => 'Bad key']]),
        ]);

        $this->expectException(SurePayException::class);

        try {
            $client->balance->get();
        } catch (SurePayException $e) {
            self::assertSame(401, $e->getHttpStatus());
            self::assertSame('unauthorized', $e->getErrorCode());
            throw $e;
        }
    }

    public function testGetThrows429OnRateLimit(): void
    {
        $client = $this->makeClient([
            'status' => 429,
            'body'   => json_encode(['error' => ['code' => 'rate_limit_exceeded', 'message' => 'Slow down']]),
        ]);

        try {
            $client->balance->get();
            self::fail('Expected SurePayException');
        } catch (SurePayException $e) {
            self::assertTrue($e->isRateLimit());
            self::assertSame(429, $e->getHttpStatus());
        }
    }
}
