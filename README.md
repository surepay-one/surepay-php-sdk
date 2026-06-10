# SurePay PHP SDK

Official PHP SDK for the [SurePay Payment Gateway](https://surepay.one) API.

## Requirements

- PHP 8.1+
- ext-curl
- ext-json

## Installation

```bash
composer require surepay/sdk
```

## Quick start

```php
use SurePay\SurePay;

$client = SurePay::builder(
    getenv('SUREPAY_API_KEY'),
    getenv('SUREPAY_API_SECRET'),
)->build();

// Check balance
$balance = $client->balance->get();
echo $balance->available;

// Create a deposit
$deposit = $client->deposits->create(
    CreateDepositRequest::builder(100_000)->withRequestId('ORD-001')->build()
);
echo $deposit->checkoutUrl;

// Create a payout
$payout = $client->payouts->create(
    CreatePayoutRequest::builder(500_000, 'VCB', '1234567890', 'NGUYEN VAN A', 'Salary')->build()
);
```

## Documentation

Full API reference: https://docs.surepay.one/docs/sdk

## License

MIT
