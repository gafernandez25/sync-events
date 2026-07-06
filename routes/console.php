<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('events:sync')->everyFifteenMinutes();
