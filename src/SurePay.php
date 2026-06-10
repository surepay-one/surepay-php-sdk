<?php

declare(strict_types=1);

namespace SurePay;

use SurePay\Internal\HttpClient;
use SurePay\Service\BalanceService;
use SurePay\Service\BankInquiryService;
use SurePay\Service\DepositsService;
use SurePay\Service\PayoutsService;
use SurePay\Service\WebhookVerifier;

/**
 * Entry point for the SurePay PHP SDK.
 *
 * <code>
 * $client = SurePay::builder(getenv('SUREPAY_API_KEY'), getenv('SUREPAY_API_SECRET'))->build();
 *
 * $balance = $client->balance->get();
 * </code>
 */
final class SurePay
{
    public const DEFAULT_BASE_URL = 'https://api.surepay.one/merchant/v1';

    public readonly BalanceService     $balance;
    public readonly DepositsService    $deposits;
    public readonly PayoutsService     $payouts;
    public readonly BankInquiryService $bankInquiry;
    public readonly WebhookVerifier    $webhooks;

    private function __construct(HttpClient $http, string $apiSecret)
    {
        $this->balance     = new BalanceService($http);
        $this->deposits    = new DepositsService($http);
        $this->payouts     = new PayoutsService($http);
        $this->bankInquiry = new BankInquiryService($http);
        $this->webhooks    = new WebhookVerifier($apiSecret);
    }

    public static function builder(string $apiKey, string $apiSecret = ''): Builder
    {
        return new Builder($apiKey, $apiSecret);
    }

    /**
     * Construct a client with an injected HTTP handler — for testing only.
     *
     * @param callable $handler fn(string $url, array $options): array{body: string, status: int}
     */
    public static function withHandler(
        string $apiKey,
        string $apiSecret,
        callable $handler,
        string $baseUrl = self::DEFAULT_BASE_URL,
    ): self {
        $http = new HttpClient(
            apiKey:         $apiKey,
            apiSecret:      $apiSecret,
            baseUrl:        $baseUrl,
            maxRetries:     0,
            timeoutSeconds: 5,
            handler:        $handler,
        );

        return new self($http, $apiSecret);
    }

}

final class Builder
{
    public string $baseUrl        = SurePay::DEFAULT_BASE_URL;
    public int    $maxRetries     = 3;
    public int    $timeoutSeconds = 30;

    public function __construct(
        public readonly string $apiKey,
        public readonly string $apiSecret,
    ) {}

    public function baseUrl(string $v): self
    {
        $this->baseUrl = $v;
        return $this;
    }

    public function maxRetries(int $v): self
    {
        $this->maxRetries = $v;
        return $this;
    }

    public function timeout(int $seconds): self
    {
        $this->timeoutSeconds = $seconds;
        return $this;
    }

    public function build(): SurePay
    {
        $http = new HttpClient(
            apiKey:         $this->apiKey,
            apiSecret:      $this->apiSecret,
            baseUrl:        $this->baseUrl,
            maxRetries:     $this->maxRetries,
            timeoutSeconds: $this->timeoutSeconds,
        );

        return new SurePay($http, $this->apiSecret);
    }
}
