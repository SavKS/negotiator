<?php

namespace Savks\Negotiator\Performance;

interface PerformanceProvider
{
    public function createEvent(string $event, array $data): Event;
}
