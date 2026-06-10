<?php

declare(strict_types=1);

namespace SurePay\Service;

use SurePay\Exception\SurePayException;
use SurePay\Internal\HttpClient;
use SurePay\Model\BankInquiryResult;
use SurePay\Request\BankInquiryRequest;

final class BankInquiryService
{
    public function __construct(private readonly HttpClient $client) {}

    /**
     * Look up account holder name before creating a payout.
     *
     * @throws SurePayException
     */
    public function verify(BankInquiryRequest $req): BankInquiryResult
    {
        $data = $this->client->execute('POST', '/bank-inquiry', $req);

        return BankInquiryResult::fromArray($data);
    }
}
