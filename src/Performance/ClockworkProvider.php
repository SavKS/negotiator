<?php

namespace Savks\Negotiator\Performance;

use Clockwork\Clockwork;

class ClockworkProvider implements PerformanceProvider
{
    protected readonly Clockwork $clockwork;

    public function __construct()
    {
        $this->clockwork = app('clockwork');
    }

    public function createEvent(string $event, array $data): Event
    {
        $event = $this->clockwork->event($event, $data);

        return new Event(
            $event->begin(...),
            $event->end(...),
        );
    }
}
