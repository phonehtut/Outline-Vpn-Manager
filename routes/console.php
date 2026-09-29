<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('keys:expire')->daily();
Schedule::command('outline:sync-keys')->everyFiveMinutes()->withoutOverlapping(10);
