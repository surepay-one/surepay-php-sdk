<?php

declare(strict_types=1);

namespace SurePay\Model;

/**
 * @template T
 */
final class ListResult
{
    /**
     * @param T[] $items
     */
    public function __construct(
        public readonly array $items,
        public readonly int $total,
        public readonly int $page,
        public readonly int $pageSize,
        public readonly int $totalPages,
    ) {}

    /**
     * @template T
     * @param array<string, mixed>  $data
     * @param array<string, mixed>  $meta
     * @param callable(array<string, mixed>): T $factory
     * @return ListResult<T>
     */
    public static function fromEnvelope(array $data, array $meta, callable $factory): self
    {
        $items = array_map(
            static fn(array $item) => $factory($item),
            $data,
        );

        return new self(
            items:      $items,
            total:      (int) ($meta['total'] ?? 0),
            page:       (int) ($meta['page'] ?? 1),
            pageSize:   (int) ($meta['page_size'] ?? count($items)),
            totalPages: (int) ($meta['total_pages'] ?? 1),
        );
    }
}
