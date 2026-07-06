<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EventResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'start_date' => $this->starts_at?->format('Y-m-d'),
            'start_time' => $this->starts_at?->format('H:i:s'),
            'end_date' => $this->ends_at?->format('Y-m-d'),
            'end_time' => $this->ends_at?->format('H:i:s'),
            'min_price' => $this->min_price,
            'max_price' => $this->max_price,
        ];
    }
}
