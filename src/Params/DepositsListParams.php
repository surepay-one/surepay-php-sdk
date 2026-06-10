<?php

declare(strict_types=1);

namespace SurePay\Params;

final class DepositsListParams
{
    private ?int $page = null;
    private ?int $pageSize = null;
    private ?string $status = null;
    private ?string $search = null;
    private ?string $fromDate = null;
    private ?string $toDate = null;

    public static function create(): self
    {
        return new self();
    }

    public function page(int $v): self
    {
        $this->page = $v;
        return $this;
    }

    public function pageSize(int $v): self
    {
        $this->pageSize = $v;
        return $this;
    }

    public function status(string $v): self
    {
        $this->status = $v;
        return $this;
    }

    public function search(string $v): self
    {
        $this->search = $v;
        return $this;
    }

    public function fromDate(string $v): self
    {
        $this->fromDate = $v;
        return $this;
    }

    public function toDate(string $v): self
    {
        $this->toDate = $v;
        return $this;
    }

    public function toQueryString(): string
    {
        $params = [];

        if ($this->page !== null) {
            $params['page'] = $this->page;
        }
        if ($this->pageSize !== null) {
            $params['page_size'] = $this->pageSize;
        }
        if ($this->status !== null && $this->status !== '') {
            $params['status'] = $this->status;
        }
        if ($this->search !== null && $this->search !== '') {
            $params['search'] = $this->search;
        }
        if ($this->fromDate !== null && $this->fromDate !== '') {
            $params['from_date'] = $this->fromDate;
        }
        if ($this->toDate !== null && $this->toDate !== '') {
            $params['to_date'] = $this->toDate;
        }

        return $params !== [] ? '?' . http_build_query($params) : '';
    }
}
