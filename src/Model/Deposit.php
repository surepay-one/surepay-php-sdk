<?php

declare(strict_types=1);

namespace SurePay\Model;

final class Deposit
{
    public function __construct(
        public readonly string $id,
        public readonly ?string $refCode,
        public readonly ?string $requestId,
        public readonly int $amount,
        public readonly string $currency,
        public readonly string $status,
        public readonly ?string $reason,
        public readonly int $fee,
        public readonly ?string $checkoutUrl,
        public readonly ?string $qrCode,
        public readonly ?string $accountNumber,
        public readonly ?string $accountName,
        public readonly ?string $bankBin,
        public readonly ?string $createdAt,
        public readonly ?string $expiresAt,
        public readonly ?string $completedAt,
        public readonly ?bool $isOwnerVerified,
        public readonly ?string $senderName,
        public readonly ?string $senderBankId,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            id:              (string) ($data['id'] ?? ''),
            refCode:         isset($data['ref_code']) ? (string) $data['ref_code'] : null,
            requestId:       isset($data['request_id']) ? (string) $data['request_id'] : null,
            amount:          (int) ($data['amount'] ?? 0),
            currency:        (string) ($data['currency'] ?? ''),
            status:          (string) ($data['status'] ?? ''),
            reason:          isset($data['reason']) ? (string) $data['reason'] : null,
            fee:             (int) ($data['fee'] ?? 0),
            checkoutUrl:     isset($data['checkout_url']) ? (string) $data['checkout_url'] : null,
            qrCode:          isset($data['qr_code']) ? (string) $data['qr_code'] : null,
            accountNumber:   isset($data['account_number']) ? (string) $data['account_number'] : null,
            accountName:     isset($data['account_name']) ? (string) $data['account_name'] : null,
            bankBin:         isset($data['bank_bin']) ? (string) $data['bank_bin'] : null,
            createdAt:       isset($data['created_at']) ? (string) $data['created_at'] : null,
            expiresAt:       isset($data['expires_at']) ? (string) $data['expires_at'] : null,
            completedAt:     isset($data['completed_at']) ? (string) $data['completed_at'] : null,
            isOwnerVerified: isset($data['is_owner_verified']) ? (bool) $data['is_owner_verified'] : null,
            senderName:      isset($data['sender_name']) ? (string) $data['sender_name'] : null,
            senderBankId:    isset($data['sender_bank_id']) ? (string) $data['sender_bank_id'] : null,
        );
    }
}
