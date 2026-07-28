<?php

namespace App\ValueObjects\Events;

use Carbon\CarbonImmutable;
use InvalidArgumentException;

final readonly class EventPayloadVO
{
    public function __construct(
        public string $title,
        public CarbonImmutable $startsAt,
        public CarbonImmutable $endsAt,
        public ?float $minPrice,
        public ?float $maxPrice,
        public CarbonImmutable $lastSyncedAt,
    ) {
        if (trim($this->title) === '') {
            throw new InvalidArgumentException('Title cannot be empty.');
        }
    }

    public static function fromExternalData(
        string $title,
        string $startsAt,
        string $endsAt,
        ?float $minPrice,
        ?float $maxPrice,
        CarbonImmutable $lastSyncedAt,
    ): self {
        return new self(
            title: $title,
            startsAt: CarbonImmutable::parse($startsAt),
            endsAt: CarbonImmutable::parse($endsAt),
            minPrice: $minPrice,
            maxPrice: $maxPrice,
            lastSyncedAt: $lastSyncedAt,
        );
    }

    /**
     * @return array{
     *     title: string,
     *     starts_at: CarbonImmutable,
     *     ends_at: CarbonImmutable,
     *     min_price: float|null,
     *     max_price: float|null,
     *     last_synced_at: CarbonImmutable
     * }
     */
    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'starts_at' => $this->startsAt,
            'ends_at' => $this->endsAt,
            'min_price' => $this->minPrice,
            'max_price' => $this->maxPrice,
            'last_synced_at' => $this->lastSyncedAt,
        ];
    }
}
