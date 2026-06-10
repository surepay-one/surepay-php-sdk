<?php

declare(strict_types=1);

namespace SurePay\Exception;

use RuntimeException;

final class SurePayException extends RuntimeException
{
    public function __construct(
        private readonly int $httpStatus,
        private readonly string $errorCode,
        string $message,
    ) {
        parent::__construct(
            sprintf('SurePay API error [HTTP %d / %s]: %s', $httpStatus, $errorCode, $message),
        );
    }

    public function getHttpStatus(): int
    {
        return $this->httpStatus;
    }

    /** @return string The machine-readable error code from the API (e.g. "not_found"). */
    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    public function isNotFound(): bool
    {
        return $this->errorCode === 'not_found';
    }

    public function isRateLimit(): bool
    {
        return $this->errorCode === 'rate_limit_exceeded';
    }

    public function isInsufficientBalance(): bool
    {
        return $this->errorCode === 'insufficient_balance';
    }

    public function isDuplicate(): bool
    {
        return $this->errorCode === 'duplicate_request';
    }
}
