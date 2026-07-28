<?php

namespace App\Repositories;

use App\Models\Event;
use App\ValueObjects\Events\EventIdentityVO;
use App\ValueObjects\Events\EventPayloadVO;
use Illuminate\Database\Eloquent\Model;

class EventRepository
{
    /**
     * @return Model<Event>
     */
    public function updateOrCreate(EventIdentityVO $identity, EventPayloadVO $payload): Model
    {
        return Event::query()->updateOrCreate(
            $identity->toArray(),
            $payload->toArray(),
        );
    }

}
