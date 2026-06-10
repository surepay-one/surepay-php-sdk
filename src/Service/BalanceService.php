<?php

declare(strict_types=1);

namespace SurePay\Service;

use SurePay\Exception\SurePayException;
use SurePay\Internal\HttpClient;
use SurePay\Model\Balance;

final class BalanceService
{
    public function __construct(private readonly HttpClient $client) {}

    /**
     * Retrieve current wallet balance, hold, and available amount.
     *
     * @throws SurePayException
     */
    public function get(): Balance
    {
        $data = $this->client->execute('GET', '/balance');

        return Balance::fromArray($data);
    }
}
