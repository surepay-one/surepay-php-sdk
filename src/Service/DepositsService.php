<?php

declare(strict_types=1);

namespace SurePay\Service;

use SurePay\Exception\SurePayException;
use SurePay\Internal\HttpClient;
use SurePay\Model\Deposit;
use SurePay\Model\ListResult;
use SurePay\Params\DepositsListParams;
use SurePay\Request\CreateDepositRequest;

final class DepositsService
{
    public function __construct(private readonly HttpClient $client) {}

    /**
     * Paginated list of deposit orders.
     *
     * @return ListResult<Deposit>
     * @throws SurePayException
     */
    public function list(?DepositsListParams $params = null): ListResult
    {
        $path = '/deposits' . ($params !== null ? $params->toQueryString() : '');
        $raw  = $this->client->executeList('GET', $path);

        return ListResult::fromEnvelope(
            $raw['items'],
            $raw['meta'],
            static fn(array $item) => Deposit::fromArray($item),
        );
    }

    /**
     * Create a new deposit order.
     *
     * @throws SurePayException
     */
    public function create(CreateDepositRequest $req, ?string $idempotencyKey = null): Deposit
    {
        $data = $this->client->execute('POST', '/deposits', $req, $idempotencyKey);

        return Deposit::fromArray($data);
    }

    /**
     * Fetch a single deposit order by its UUID.
     *
     * @throws SurePayException
     */
    public function get(string $id): Deposit
    {
        $data = $this->client->execute('GET', '/deposits/' . $id);

        return Deposit::fromArray($data);
    }
}
