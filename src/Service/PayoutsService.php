<?php

declare(strict_types=1);

namespace SurePay\Service;

use SurePay\Exception\SurePayException;
use SurePay\Internal\HttpClient;
use SurePay\Model\ListResult;
use SurePay\Model\Payout;
use SurePay\Params\PayoutsListParams;
use SurePay\Request\CreatePayoutRequest;

final class PayoutsService
{
    public function __construct(private readonly HttpClient $client) {}

    /**
     * Paginated list of payout orders.
     *
     * @return ListResult<Payout>
     * @throws SurePayException
     */
    public function list(?PayoutsListParams $params = null): ListResult
    {
        $path = '/payouts' . ($params !== null ? $params->toQueryString() : '');
        $raw  = $this->client->executeList('GET', $path);

        return ListResult::fromEnvelope(
            $raw['items'],
            $raw['meta'],
            static fn(array $item) => Payout::fromArray($item),
        );
    }

    /**
     * Initiate a payout bank transfer from your balance.
     *
     * @throws SurePayException
     */
    public function create(CreatePayoutRequest $req, ?string $idempotencyKey = null): Payout
    {
        $data = $this->client->execute('POST', '/payouts', $req, $idempotencyKey);

        return Payout::fromArray($data);
    }

    /**
     * Fetch a single payout by its UUID.
     *
     * @throws SurePayException
     */
    public function get(string $id): Payout
    {
        $data = $this->client->execute('GET', '/payouts/' . $id);

        return Payout::fromArray($data);
    }
}
