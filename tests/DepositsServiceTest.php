<?php

declare(strict_types=1);

namespace SurePay\Tests;

use PHPUnit\Framework\TestCase;
use SurePay\Exception\SurePayException;
use SurePay\Params\DepositsListParams;
use SurePay\Request\CreateDepositRequest;
use SurePay\SurePay;

final class DepositsServiceTest extends TestCase
{
    private function makeClient(array $response): SurePay
    {
        $handler = static fn(string $url, array $options): array => $response;

        return SurePay::withHandler('test-key', 'test-secret', $handler);
    }

    private function depositFixture(string $id = 'dep-001'): array
    {
        return [
            'id'           => $id,
            'amount'       => 100_000,
            'currency'     => 'VND',
            'status'       => 'pending',
            'fee'          => 0,
            'checkout_url' => 'https://pay.surepay.one/checkout/' . $id,
        ];
    }

    public function testListReturnsDeposits(): void
    {
        // API response: {"data": [...], "meta": {...}}
        $client = $this->makeClient([
            'status' => 200,
            'body'   => json_encode([
                'data' => [$this->depositFixture(), $this->depositFixture('dep-002')],
                'meta' => ['total' => 2, 'page' => 1, 'page_size' => 20, 'total_pages' => 1],
            ]),
        ]);

        $result = $client->deposits->list();

        self::assertCount(2, $result->items);
        self::assertSame('dep-001', $result->items[0]->id);
        self::assertSame(2, $result->total);
    }

    public function testListWithParams(): void
    {
        $capturedUrl = null;
        $handler = static function (string $url, array $options) use (&$capturedUrl): array {
            $capturedUrl = $url;
            return [
                'status' => 200,
                'body'   => json_encode([
                    'data' => [],
                    'meta' => ['total' => 0, 'page' => 2, 'page_size' => 10, 'total_pages' => 0],
                ]),
            ];
        };

        $client = SurePay::withHandler('test-key', 'test-secret', $handler);
        $client->deposits->list(
            DepositsListParams::create()->page(2)->pageSize(10)->status('success'),
        );

        self::assertStringContainsString('page=2', $capturedUrl);
        self::assertStringContainsString('page_size=10', $capturedUrl);
        self::assertStringContainsString('status=success', $capturedUrl);
    }

    public function testCreateReturnsDeposit(): void
    {
        $client = $this->makeClient([
            'status' => 201,
            'body'   => json_encode(['data' => $this->depositFixture()]),
        ]);

        $req     = CreateDepositRequest::builder(100_000)->withRequestId('ORD-001');
        $deposit = $client->deposits->create($req);

        self::assertSame('dep-001', $deposit->id);
        self::assertSame(100_000, $deposit->amount);
    }

    public function testCreateSendsIdempotencyKey(): void
    {
        $capturedHeaders = null;
        $handler = static function (string $url, array $options) use (&$capturedHeaders): array {
            $capturedHeaders = $options['headers'];
            return [
                'status' => 201,
                'body'   => json_encode(['data' => [
                    'id' => 'dep-001', 'amount' => 100_000, 'currency' => 'VND',
                    'status' => 'pending', 'fee' => 0,
                ]]),
            ];
        };

        $client = SurePay::withHandler('test-key', 'test-secret', $handler);
        $client->deposits->create(
            CreateDepositRequest::builder(100_000),
            'idem-key-123',
        );

        $headerStr = implode("\n", $capturedHeaders);
        self::assertStringContainsString('Idempotency-Key: idem-key-123', $headerStr);
    }

    public function testGetReturnsDeposit(): void
    {
        $client = $this->makeClient([
            'status' => 200,
            'body'   => json_encode(['data' => $this->depositFixture('dep-xyz')]),
        ]);

        $deposit = $client->deposits->get('dep-xyz');

        self::assertSame('dep-xyz', $deposit->id);
    }

    public function testGetThrows404(): void
    {
        $client = $this->makeClient([
            'status' => 404,
            'body'   => json_encode(['error' => ['code' => 'not_found', 'message' => 'Not found']]),
        ]);

        try {
            $client->deposits->get('nonexistent');
            self::fail('Expected SurePayException');
        } catch (SurePayException $e) {
            self::assertTrue($e->isNotFound());
            self::assertSame(404, $e->getHttpStatus());
        }
    }

    public function testGetThrows429RateLimit(): void
    {
        $client = $this->makeClient([
            'status' => 429,
            'body'   => json_encode(['error' => ['code' => 'rate_limit_exceeded', 'message' => 'Too many requests']]),
        ]);

        try {
            $client->deposits->get('dep-001');
            self::fail('Expected SurePayException');
        } catch (SurePayException $e) {
            self::assertTrue($e->isRateLimit());
        }
    }
}
