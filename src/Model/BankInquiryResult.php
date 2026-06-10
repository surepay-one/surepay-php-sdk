<?php

declare(strict_types=1);

namespace SurePay\Model;

final class BankInquiryResult
{
    public function __construct(
        public readonly string $bankCode,
        public readonly string $accountNumber,
        public readonly string $accountName,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            bankCode:      (string) ($data['bank_code'] ?? ''),
            accountNumber: (string) ($data['account_number'] ?? ''),
            accountName:   (string) ($data['account_name'] ?? ''),
        );
    }
}
