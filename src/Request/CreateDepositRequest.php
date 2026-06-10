<?php

declare(strict_types=1);

namespace SurePay\Request;

final class CreateDepositRequest implements \JsonSerializable
{
    public function __construct(
        public readonly int $amount,
        public readonly ?string $currency = null,
        public readonly ?string $requestId = null,
        public readonly ?string $senderBankId = null,
        public readonly ?string $senderBankName = null,
        public readonly ?string $senderAccount = null,
        public readonly ?string $senderName = null,
    ) {}

    public static function builder(int $amount): self
    {
        return new self(amount: $amount);
    }

    public function withCurrency(string $currency): self
    {
        return new self(
            amount:         $this->amount,
            currency:       $currency,
            requestId:      $this->requestId,
            senderBankId:   $this->senderBankId,
            senderBankName: $this->senderBankName,
            senderAccount:  $this->senderAccount,
            senderName:     $this->senderName,
        );
    }

    public function withRequestId(string $requestId): self
    {
        return new self(
            amount:         $this->amount,
            currency:       $this->currency,
            requestId:      $requestId,
            senderBankId:   $this->senderBankId,
            senderBankName: $this->senderBankName,
            senderAccount:  $this->senderAccount,
            senderName:     $this->senderName,
        );
    }

    public function withSenderBankId(string $senderBankId): self
    {
        return new self(
            amount:         $this->amount,
            currency:       $this->currency,
            requestId:      $this->requestId,
            senderBankId:   $senderBankId,
            senderBankName: $this->senderBankName,
            senderAccount:  $this->senderAccount,
            senderName:     $this->senderName,
        );
    }

    public function withSenderBankName(string $senderBankName): self
    {
        return new self(
            amount:         $this->amount,
            currency:       $this->currency,
            requestId:      $this->requestId,
            senderBankId:   $this->senderBankId,
            senderBankName: $senderBankName,
            senderAccount:  $this->senderAccount,
            senderName:     $this->senderName,
        );
    }

    public function withSenderAccount(string $senderAccount): self
    {
        return new self(
            amount:         $this->amount,
            currency:       $this->currency,
            requestId:      $this->requestId,
            senderBankId:   $this->senderBankId,
            senderBankName: $this->senderBankName,
            senderAccount:  $senderAccount,
            senderName:     $this->senderName,
        );
    }

    public function withSenderName(string $senderName): self
    {
        return new self(
            amount:         $this->amount,
            currency:       $this->currency,
            requestId:      $this->requestId,
            senderBankId:   $this->senderBankId,
            senderBankName: $this->senderBankName,
            senderAccount:  $this->senderAccount,
            senderName:     $senderName,
        );
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        $data = ['amount' => $this->amount];

        if ($this->currency !== null) {
            $data['currency'] = $this->currency;
        }
        if ($this->requestId !== null) {
            $data['request_id'] = $this->requestId;
        }
        if ($this->senderBankId !== null) {
            $data['sender_bank_id'] = $this->senderBankId;
        }
        if ($this->senderBankName !== null) {
            $data['sender_bank_name'] = $this->senderBankName;
        }
        if ($this->senderAccount !== null) {
            $data['sender_account'] = $this->senderAccount;
        }
        if ($this->senderName !== null) {
            $data['sender_name'] = $this->senderName;
        }

        return $data;
    }
}
