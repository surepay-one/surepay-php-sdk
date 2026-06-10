<?php

declare(strict_types=1);

namespace SurePay\Tests;

use PHPUnit\Framework\TestCase;
use SurePay\Exception\SurePayException;
use SurePay\Params\PayoutsListParams;
use SurePay\Request\CreatePayoutRequest;
use SurePay\SurePay;

final class PayoutsServiceTest extends TestCase
{
    private function makeClient(array $response): SurePay
    {
        $handler = static fn(string $url, array $options): array => $response;

        return SurePay::withHandler('test-key', 'test-secret', $handler);
    }

    private function payoutFixture(string $id = 'pay-001'): array
    {
        return [
            'id'          => $id,
            'amount'      => 500_000,
            'fee'         => 2_000,
            'status'      => 'pending',
            'currency'    => 'VND',
            'bank_code'   => 'VCB',
            'bank_account' => '1234567890',
            'full_name'   => 'NGUYEN VAN A',
            'description' => 'Salary June',
        ];
    }

    public function testListReturnsPayout(): void
    {
        $client = $this->makeClient([
            'status' => 200,
            'body'   => json_encode([
                'data' => [$this->payoutFixture()],
                'meta' => ['total' => 1, 'page' => 1, 'page_size' => 20, 'total_pages' => 1],
            ]),
        ]);

        $result = $client->payouts->list();

        self::assertCount(1, $result->items);
        self::assertSame('pay-001', $result->items[0]->id);
        self::assertSame(1, $result->total);
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
                    'meta' => ['total' => 0, 'page' => 1, 'page_size' => 10, 'total_pages' => 0],
                ]),
            ];
        };

        $client = SurePay::withHandler('test-key', 'test-secret', $handler);
        $client->payouts->list(
            PayoutsListParams::create()->page(1)->status('success'),
        );

        self::assertStringContainsString('status=success', $capturedUrl);
    }

    public function testCreateReturnsPayout(): void
    {
        $client = $this->makeClient([
            'status' => 201,
            'body'   => json_encode(['data' => $this->payoutFixture()]),
        ]);

        $req    = new CreatePayoutRequest(500_000, 'VCB', '1234567890', 'NGUYEN VAN A', 'Salary June');
        $payout = $client->payouts->create($req);

        self::assertSame('pay-001', $payout->id);
        self::assertSame(500_000, $payout->amount);
        self::assertSame('VCB', $payout->bankCode);
    }

    public function testCreateWithIdempotencyKey(): void
    {
        $capturedHeaders = null;
        $handler = static function (string $url, array $options) use (&$capturedHeaders): array {
            $capturedHeaders = $options['headers'];
            return [
                'status' => 201,
                'body'   => json_encode(['data' => [
                    'id' => 'pay-001', 'amount' => 500_000, 'fee' => 0, 'status' => 'pending',
                    'bank_code' => 'VCB', 'bank_account' => '1234567890',
                    'full_name' => 'NGUYEN VAN A', 'description' => 'Salary',
                ]]),
            ];
        };

        $client = SurePay::withHandler('test-key', 'test-secret', $handler);
        $client->payouts->create(
            new CreatePayoutRequest(500_000, 'VCB', '1234567890', 'NGUYEN VAN A', 'Salary'),
            'idem-pay-001',
        );

        $headerStr = implode("\n", $capturedHeaders);
        self::assertStringContainsString('Idempotency-Key: idem-pay-001', $headerStr);
    }

    public function testGetReturnsPayout(): void
    {
        $client = $this->makeClient([
            'status' => 200,
            'body'   => json_encode(['data' => $this->payoutFixture('pay-xyz')]),
        ]);

        $payout = $client->payouts->get('pay-xyz');

        self::assertSame('pay-xyz', $payout->id);
    }

    public function testGetThrows404(): void
    {
        $client = $this->makeClient([
            'status' => 404,
            'body'   => json_encode(['error' => ['code' => 'not_found', 'message' => 'Not found']]),
        ]);

        try {
            $client->payouts->get('missing');
            self::fail('Expected SurePayException');
        } catch (SurePayException $e) {
            self::assertTrue($e->isNotFound());
        }
    }

    public function testInsufficientBalanceException(): void
    {
        $client = $this->makeClient([
            'status' => 422,
            'body'   => json_encode(['error' => ['code' => 'insufficient_balance', 'message' => 'Top up first']]),
        ]);

        try {
            $client->payouts->create(
                new CreatePayoutRequest(99_999_999, 'VCB', '1234567890', 'NGUYEN VAN A', 'Test'),
            );
            self::fail('Expected SurePayException');
        } catch (SurePayException $e) {
            self::assertTrue($e->isInsufficientBalance());
            self::assertSame(422, $e->getHttpStatus());
        }
    }

    public function testDuplicateRequestException(): void
    {
        $client = $this->makeClient([
            'status' => 409,
            'body'   => json_encode(['error' => ['code' => 'duplicate_request', 'message' => 'Already exists']]),
        ]);

        try {
            $client->payouts->create(
                new CreatePayoutRequest(100_000, 'VCB', '1234567890', 'NGUYEN VAN A', 'Test'),
                'dup-key',
            );
            self::fail('Expected SurePayException');
        } catch (SurePayException $e) {
            self::assertTrue($e->isDuplicate());
        }
    }
}
