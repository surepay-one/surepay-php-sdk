<?php

declare(strict_types=1);

namespace SurePay\Model;

final class Payout
{
    public function __construct(
        public readonly string $id,
        public readonly ?string $refCode,
        public readonly int $amount,
        public readonly int $fee,
        public readonly string $status,
        public readonly ?string $reason,
        public readonly string $bankCode,
        public readonly string $bankAccount,
        public readonly ?string $bankName,
        public readonly string $fullName,
        public readonly string $description,
        public readonly ?string $createdAt,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            id:          (string) ($data['id'] ?? ''),
            refCode:     isset($data['ref_code']) ? (string) $data['ref_code'] : null,
            amount:      (int) ($data['amount'] ?? 0),
            fee:         (int) ($data['fee'] ?? 0),
            status:      (string) ($data['status'] ?? ''),
            reason:      isset($data['reason']) ? (string) $data['reason'] : null,
            bankCode:    (string) ($data['bank_code'] ?? ''),
            bankAccount: (string) ($data['bank_account'] ?? ''),
            bankName:    isset($data['bank_name']) ? (string) $data['bank_name'] : null,
            fullName:    (string) ($data['full_name'] ?? ''),
            description: (string) ($data['description'] ?? ''),
            createdAt:   isset($data['created_at']) ? (string) $data['created_at'] : null,
        );
    }
}
