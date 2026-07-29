<?php

namespace App\Services;

use App\Repositories\EventRepository;
use App\Services\Contracts\EventsExternalSourceInterface;
use App\ValueObjects\Events\EventIdentityVO;
use App\ValueObjects\Events\EventPayloadVO;
use Carbon\CarbonImmutable;
use SimpleXMLElement;

class TestEventsExternalSourceStrategy implements EventsExternalSourceInterface
{
    private const string PROVIDER = 'test_provider';

    public function __construct(
        private readonly ExternalEventsService $externalEventsService,
        private readonly XmlService $xmlService,
        private readonly EventRepository $eventRepository,
    ) {
    }

    public function name(): string
    {
        return self::PROVIDER;
    }

    public function sync(): int
    {
        $xmlContent = $this->externalEventsService->fetchExternalEvents(
            uri: config('services.test_provider.url'),
            headers: [
                'Accept' => 'application/xml',
            ],
        );

        $xml = $this->xmlService->parseXml($xmlContent);

        $syncedEvents = 0;

        foreach ($xml->xpath('//base_plan') ?: [] as $basePlan) {
            $basePlanAttributes = $basePlan->attributes();

            $sellMode = (string)($basePlanAttributes['sell_mode'] ?? '');

            if ($sellMode !== 'online') {
                continue;
            }

            $externalBasePlanId = (string)($basePlanAttributes['base_plan_id'] ?? '');
            $title = (string)($basePlanAttributes['title'] ?? '');

            if ($externalBasePlanId === '' || $title === '') {
                continue;
            }

            foreach ($basePlan->xpath('.//plan') ?: [] as $plan) {
                $planAttributes = $plan->attributes();

                $externalPlanId = (string)($planAttributes['plan_id'] ?? '');
                $startsAt = (string)($planAttributes['plan_start_date'] ?? '');
                $endsAt = (string)($planAttributes['plan_end_date'] ?? '');

                if ($externalPlanId === '' || $startsAt === '' || $endsAt === '') {
                    continue;
                }

                [$minPrice, $maxPrice] = $this->extractPrices($plan);

                $this->eventRepository->updateOrCreate(
                    new EventIdentityVO(
                        provider: self::PROVIDER,
                        externalBasePlanId: $externalBasePlanId,
                        externalPlanId: $externalPlanId,
                    ),
                    new EventPayloadVO(
                        title: $title,
                        startsAt: CarbonImmutable::parse($startsAt),
                        endsAt: CarbonImmutable::parse($endsAt),
                        minPrice: $minPrice,
                        maxPrice: $maxPrice,
                        lastSyncedAt: CarbonImmutable::now(),
                    ),
                );

                $syncedEvents++;
            }
        }

        return $syncedEvents;
    }

    /**
     * @return array{0: float|null, 1: float|null}
     */
    private function extractPrices(SimpleXMLElement $plan): array
    {
        $prices = [];

        foreach ($plan->xpath('.//zone') ?: [] as $zone) {
            $zoneAttributes = $zone->attributes();

            $price = (string)($zoneAttributes['price'] ?? '');

            if ($price !== '' && is_numeric($price)) {
                $prices[] = (float)$price;
            }
        }

        if ($prices === []) {
            return [null, null];
        }

        return [
            min($prices),
            max($prices),
        ];
    }
}
