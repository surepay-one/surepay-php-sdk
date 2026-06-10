<?php

declare(strict_types=1);

namespace SurePay\Request;

final class CreatePayoutRequest implements \JsonSerializable
{
    public function __construct(
        public readonly int $amount,
        public readonly string $bankCode,
        public readonly string $bankAccount,
        public readonly string $accountName,
        public readonly string $memo,
        public readonly ?string $bankName = null,
    ) {}

    public function withBankName(string $bankName): self
    {
        return new self(
            amount:      $this->amount,
            bankCode:    $this->bankCode,
            bankAccount: $this->bankAccount,
            accountName: $this->accountName,
            memo:        $this->memo,
            bankName:    $bankName,
        );
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        $data = [
            'amount'       => $this->amount,
            'bank_code'    => $this->bankCode,
            'bank_account' => $this->bankAccount,
            'account_name' => $this->accountName,
            'memo'         => $this->memo,
        ];

        if ($this->bankName !== null) {
            $data['bank_name'] = $this->bankName;
        }

        return $data;
    }
}
