<?php

declare(strict_types=1);

namespace SurePay\Model;

final class Balance
{
    public function __construct(
        public readonly int $balance,
        public readonly int $hold,
        public readonly int $available,
        public readonly string $currency,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            balance:   (int) ($data['balance'] ?? 0),
            hold:      (int) ($data['hold'] ?? 0),
            available: (int) ($data['available'] ?? 0),
            currency:  (string) ($data['currency'] ?? ''),
        );
    }
}
