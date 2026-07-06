<?php

namespace App\Http\Controllers;

use App\Http\Requests\Api\V1\IndexRequest;
use App\Http\Resources\Api\V1\EventResource;
use App\Models\Event;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;

class SearchEventsController extends Controller
{
    private const int LIMIT = 100;

    public function index(IndexRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $startsAt = CarbonImmutable::parse($validated['starts_at'])->toDateTimeString();
        $endsAt = CarbonImmutable::parse($validated['ends_at'])->toDateTimeString();

        $events = Event::query()
            ->where('starts_at', '>=', $startsAt)
            ->where('ends_at', '<=', $endsAt)
            ->orderBy('starts_at')
            ->limit(self::LIMIT)
            ->get();

        return response()->json([
            'data' => [
                'events' => EventResource::collection($events)->resolve($request),
            ],
            'error' => null,
        ]);
    }
}
