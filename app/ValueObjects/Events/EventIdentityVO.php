<?php

namespace App\ValueObjects\Events;

use InvalidArgumentException;

final readonly class EventIdentityVO
{
    public function __construct(
        public string $provider,
        public string $externalBasePlanId,
        public string $externalPlanId,
    ) {
        if (trim($this->provider) === '') {
            throw new InvalidArgumentException('Provider cannot be empty.');
        }

        if (trim($this->externalBasePlanId) === '') {
            throw new InvalidArgumentException('External base plan id cannot be empty.');
        }

        if (trim($this->externalPlanId) === '') {
            throw new InvalidArgumentException('External plan id cannot be empty.');
        }
    }

    /**
     * @return array{
     *     provider: string,
     *     external_base_plan_id: string,
     *     external_plan_id: string
     * }
     */
    public function toArray(): array
    {
        return [
            'provider' => $this->provider,
            'external_base_plan_id' => $this->externalBasePlanId,
            'external_plan_id' => $this->externalPlanId,
        ];
    }
}
