<?php

namespace App\Services;

use App\Models\Event;
use App\Services\Contracts\EventsExternalSourceInterface;
use Carbon\CarbonImmutable;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use RuntimeException;
use SimpleXMLElement;

class FeverUpEventsExternalSourceStrategy implements EventsExternalSourceInterface
{
    private const string PROVIDER = 'fever_provider';

    public function __construct(
        private readonly Client $client,
    ) {
    }

    public function name(): string{
        return self::PROVIDER;
    }

    public function sync(): int
    {
        $xmlContent = $this->fetchExternalEvents();

        $xml = $this->parseXml($xmlContent);

        $syncedEvents = 0;
        $now = now();

        foreach ($xml->xpath('//base_plan') ?: [] as $basePlan) {
            $basePlanAttributes = $basePlan->attributes();

            $sellMode = (string) ($basePlanAttributes['sell_mode'] ?? '');

            if ($sellMode !== 'online') {
                continue;
            }

            $externalBasePlanId = (string) ($basePlanAttributes['base_plan_id'] ?? '');
            $title = (string) ($basePlanAttributes['title'] ?? '');

            if ($externalBasePlanId === '' || $title === '') {
                continue;
            }

            foreach ($basePlan->xpath('.//plan') ?: [] as $plan) {
                $planAttributes = $plan->attributes();

                $externalPlanId = (string) ($planAttributes['plan_id'] ?? '');
                $startsAt = (string) ($planAttributes['plan_start_date'] ?? '');
                $endsAt = (string) ($planAttributes['plan_end_date'] ?? '');

                if ($externalPlanId === '' || $startsAt === '' || $endsAt === '') {
                    continue;
                }

                [$minPrice, $maxPrice] = $this->extractPrices($plan);

                Event::query()->updateOrCreate(
                    [
                        'provider' => self::PROVIDER,
                        'external_base_plan_id' => $externalBasePlanId,
                        'external_plan_id' => $externalPlanId,
                    ],
                    [
                        'title' => $title,
                        'starts_at' => CarbonImmutable::parse($startsAt),
                        'ends_at' => CarbonImmutable::parse($endsAt),
                        'min_price' => $minPrice,
                        'max_price' => $maxPrice,
                        'last_seen_at' => $now,
                    ],
                );

                $syncedEvents++;
            }
        }

        return $syncedEvents;
    }

    private function fetchExternalEvents(): string
    {
        try {
            $response = $this->client->request('GET', config('services.fever_provider.url'), [
                'timeout' => 5,
                'connect_timeout' => 2,
                'http_errors' => false,
                'headers' => [
                    'Accept' => 'application/xml',
                ],
            ]);
        } catch (GuzzleException $exception) {
            throw new RuntimeException('Could not connect to external events provider.', previous: $exception);
        }

        $statusCode = $response->getStatusCode();

        if ($statusCode < 200 || $statusCode >= 300) {
            throw new RuntimeException("External events provider returned HTTP status {$statusCode}.");
        }

        return $response->getBody()->getContents();
    }

    private function parseXml(string $xmlContent): SimpleXMLElement
    {
        libxml_use_internal_errors(true);

        $xml = simplexml_load_string($xmlContent);

        libxml_clear_errors();

        if (! $xml instanceof SimpleXMLElement) {
            throw new RuntimeException('Invalid XML received from external events provider.');
        }

        return $xml;
    }

    /**
     * @return array{0: float|null, 1: float|null}
     */
    private function extractPrices(SimpleXMLElement $plan): array
    {
        $prices = [];

        foreach ($plan->xpath('.//zone') ?: [] as $zone) {
            $zoneAttributes = $zone->attributes();

            $price = (string) ($zoneAttributes['price'] ?? '');

            if ($price !== '' && is_numeric($price)) {
                $prices[] = (float) $price;
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
