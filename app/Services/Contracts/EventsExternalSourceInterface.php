<?php

namespace App\Services\Contracts;

interface EventsExternalSourceInterface
{
    public function sync(): int;

    public function name(): string;
}
