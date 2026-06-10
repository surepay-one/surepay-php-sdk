<?php

declare(strict_types=1);

namespace SurePay\Request;

final class BankInquiryRequest implements \JsonSerializable
{
    public function __construct(
        public readonly string $bankCode,
        public readonly string $bankAccount,
    ) {}

    public static function of(string $bankCode, string $bankAccount): self
    {
        return new self($bankCode, $bankAccount);
    }

    /** @return array<string, string> */
    public function jsonSerialize(): array
    {
        return [
            'bank_code'    => $this->bankCode,
            'bank_account' => $this->bankAccount,
        ];
    }
}
