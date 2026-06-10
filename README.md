# surepay-php-sdk

Official PHP client library for the [SurePay](https://surepay.one) Merchant API.

[![CI](https://github.com/surepay-one/surepay-php-sdk/actions/workflows/ci.yml/badge.svg)](https://github.com/surepay-one/surepay-php-sdk/actions/workflows/ci.yml)
[![Packagist](https://img.shields.io/packagist/v/surepay/sdk)](https://packagist.org/packages/surepay/sdk)

## Requirements

- PHP 8.1+
- ext-curl
- ext-json
- Zero non-stdlib dependencies

## Install

```bash
composer require surepay/sdk
```

## Quick start

```php
use SurePay\SurePay;
use SurePay\Request\CreateDepositRequest;
use SurePay\Request\CreatePayoutRequest;

$client = SurePay::builder(
    getenv('SUREPAY_API_KEY'),    // tpay_live_... or tpay_test_...
    getenv('SUREPAY_API_SECRET'), // tpay_sec_... — enables auto HMAC signing
)->build();

// Check wallet balance
$balance = $client->balance->get();
echo "Available: {$balance->available} VND\n";

// Create a deposit order (thu hộ)
$deposit = $client->deposits->create(
    CreateDepositRequest::builder(100_000)
        ->withRequestId('ORD-20260610-001')
        ->build()
);
echo "Checkout URL: {$deposit->checkoutUrl}\n";

// Create a payout (chi hộ)
try {
    $payout = $client->payouts->create(
        CreatePayoutRequest::builder(500_000, 'VCB', '1234567890', 'NGUYEN VAN A', 'Salary June 2026')
            ->build()
    );
    echo "Payout ID: {$payout->id}\n";
} catch (SurePayException $e) {
    if ($e->isInsufficientBalance()) {
        echo "Not enough balance — top up first\n";
    }
}
```

## Configuration

```php
$client = SurePay::builder($apiKey, $apiSecret)
    ->baseUrl('https://api.surepay.one/merchant/v1') // override for local/staging
    ->timeout(15)                                     // seconds
    ->maxRetries(3)                                   // retries on 5xx and network errors
    ->build();
```

| Option | Default | Description |
|--------|---------|-------------|
| `baseUrl(string)` | `https://api.surepay.one/merchant/v1` | Override base URL for local dev or staging |
| `timeout(int)` | `30` | HTTP request timeout in seconds |
| `maxRetries(int)` | `3` | Retry attempts on 5xx and network errors |

## Authentication

Every request requires an API key sent as an `X-API-Key` header. When `apiSecret` is provided to `builder()`, every outgoing request is automatically signed with HMAC-SHA256 — the `X-Signature` and `X-Timestamp` headers are attached with no extra code needed.

## API reference

### Balance

#### `$client->balance->get()`

Get current wallet balance. **Requires:** `balance:read` scope.

```php
$balance = $client->balance->get();
// $balance->balance   — total wallet balance in VND
// $balance->hold      — reserved for in-flight transactions
// $balance->available — balance - hold
// $balance->currency  — always "VND"
```

---

### Deposits

#### `$client->deposits->list(params)`

Paginated list of deposit (thu hộ) orders. **Requires:** `deposits:read` scope.

```php
use SurePay\Params\DepositsListParams;

$result = $client->deposits->list(
    DepositsListParams::create()
        ->page(1)
        ->pageSize(20)
        ->status('success')       // pending|processing|success|failed|expired|cancelled
        ->fromDate('2026-06-01')  // YYYY-MM-DD
        ->toDate('2026-06-30')
);
// $result->items      — Deposit[]
// $result->total      — total matching records
// $result->totalPages — total pages
```

#### `$client->deposits->create(req)`

Create a new deposit order. Returns a `checkoutUrl` (redirect) and `qrCode` (VietQR). **Requires:** `deposits:write` scope.

```php
use SurePay\Request\CreateDepositRequest;

$deposit = $client->deposits->create(
    CreateDepositRequest::builder(100_000)         // amount in VND, required
        ->withRequestId('ORD-20260610-001')        // your order ID — optional, for idempotency
        // Chính chủ verification — all optional:
        ->withSenderBankId('970436')
        ->withSenderBankName('Vietcombank')
        ->withSenderAccount('1234567890')
        ->withSenderName('NGUYEN VAN A')
        ->build()
);
echo $deposit->checkoutUrl;
echo $deposit->qrCode;
```

**Response fields:**

| Field | Type | Description |
|-------|------|-------------|
| `id` | string | SurePay transaction UUID |
| `requestId` | string | Your order ID |
| `amount` | int | Amount in VND |
| `status` | string | pending, processing, success, failed, expired, cancelled |
| `checkoutUrl` | string | Redirect URL for payer |
| `qrCode` | string | VietQR data string |
| `createdAt` | string | ISO 8601 timestamp |
| `updatedAt` | string | ISO 8601 timestamp |

#### `$client->deposits->get(id)`

Fetch a single deposit order by UUID. **Requires:** `deposits:read` scope.

```php
$deposit = $client->deposits->get('uuid-here');
// $deposit->status: 'pending' | 'success' | ...
```

---

### Payouts

#### `$client->payouts->list(params)`

Paginated list of payout (chi hộ) orders. **Requires:** `payouts:read` scope.

```php
use SurePay\Params\PayoutsListParams;

$result = $client->payouts->list(
    PayoutsListParams::create()
        ->page(1)
        ->pageSize(20)
        ->status('success')   // pending|processing|success|failed
        ->fromDate('2026-06-01')
        ->toDate('2026-06-30')
);
```

#### `$client->payouts->create(req)`

Initiate a payout bank transfer. Funds are deducted from your wallet immediately on success. **Requires:** `payouts:write` scope.

> Payouts are irreversible once status moves past `pending`. Verify bank details with `$client->bankInquiry->verify()` first.

```php
use SurePay\Request\CreatePayoutRequest;

$payout = $client->payouts->create(
    CreatePayoutRequest::builder(
        500_000,        // amount in VND, required
        'VCB',          // bank code, required (VCB, MB, TCB, ACB, ...)
        '1234567890',   // bank account, required
        'NGUYEN VAN A', // full name in UPPERCASE, required
        'Salary June'   // description / transfer memo, required
    )
    ->withBankName('Vietcombank') // optional
    ->build()
);
```

#### `$client->payouts->get(id)`

Fetch a single payout by UUID. **Requires:** `payouts:read` scope.

```php
$payout = $client->payouts->get('uuid-here');
// $payout->status: 'pending' | 'success' | ...
```

---

### Bank Inquiry

#### `$client->bankInquiry->verify(req)`

Look up the account holder name for a bank account. Call this before creating a payout to confirm the recipient. **Requires:** `payouts:read` scope.

```php
use SurePay\Request\BankInquiryRequest;

$result = $client->bankInquiry->verify(
    BankInquiryRequest::of('VCB', '1234567890')
);
echo "Account name: {$result->accountName}\n";
```

---

## Idempotency

Pass an idempotency key as the second argument to any `create()` method. The key is forwarded as an `Idempotency-Key` header — safe to retry on network errors without risk of duplicate transactions.

```php
$deposit = $client->deposits->create(
    CreateDepositRequest::builder(100_000)->withRequestId('ORD-001')->build(),
    'ORD-001'  // idempotency key
);
```

## Webhook verification

Every inbound webhook event from SurePay is HMAC-signed. Pass the **raw** request body string (before any JSON parsing) to `$client->webhooks->verify()`:

```php
// Laravel example
Route::post('/webhook/surepay', function (Request $request) {
    $body = $request->getContent();

    if (!$client->webhooks->verify($body)) {
        abort(401, 'Invalid signature');
    }

    $event = json_decode($body, true);

    match ($event['event']) {
        'deposit.success', 'deposit.failed' => handleDeposit($event),
        'payout.success',  'payout.failed'  => handlePayout($event),
        default => null,
    };

    return response()->noContent();
});
```

Or pass the `X-Surepay-Signature` header value explicitly:

```php
$sig   = $request->header('X-Surepay-Signature');
$valid = $client->webhooks->verifyWithSignature($body, $sig);
```

## Error handling

```php
use SurePay\Exception\SurePayException;

try {
    $payout = $client->payouts->create($req);
} catch (SurePayException $e) {
    // Convenience helpers
    if ($e->isNotFound())            { /* 404 not_found */ }
    if ($e->isRateLimit())           { /* 429 rate_limit_exceeded */ }
    if ($e->isInsufficientBalance()) { /* 422 insufficient_balance */ }
    if ($e->isDuplicate())           { /* 409 duplicate_request */ }

    // Full details
    printf("HTTP %d  code=%s  %s\n",
        $e->getHttpStatus(), $e->getErrorCode(), $e->getMessage());
}
```

**Error codes:**

| HTTP | `getErrorCode()` | Meaning |
|------|-----------------|---------|
| 400 | `validation_error` | Invalid request body or parameters |
| 401 | `unauthorized` | Missing or invalid API key |
| 401 | `signature_invalid` | HMAC signature failed or timestamp > 5 min |
| 403 | `permission_denied` | API key lacks required scope |
| 403 | `ip_not_allowed` | Request IP not in allowlist |
| 404 | `not_found` | Resource not found |
| 409 | `duplicate_request` | Idempotency key conflict |
| 422 | `insufficient_balance` | Top up wallet first |
| 422 | `invalid_state_transition` | Operation not allowed for current status |
| 429 | `rate_limit_exceeded` | Slow down — back off and retry |
| 500 | `internal_error` | Server error |

## HMAC signing

When `apiSecret` is set, all requests are signed automatically. The signing algorithm for manual use:

```
signingString = UNIX_TIMESTAMP . "\n" . METHOD . "\n" . PATH . "\n" . hex(sha256(body))
signature     = "sha256=" . hex(hash_hmac('sha256', signingString, apiSecret))
```

Attach as headers: `X-Signature: <signature>` and `X-Timestamp: <unix_timestamp>`.

Signatures expire after **300 seconds** — generate per-request, never cache or reuse.

## License

MIT
