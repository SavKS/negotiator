<?php

namespace Savks\Negotiator\Performance;

use Savks\Negotiator\Enums\PerformanceTrackers;

class Performance
{
    protected readonly PerformanceProvider $provider;

    /**
     * @var array{
     *     mappers: bool
     * }
     */
    protected readonly array $trackers;

    public function __construct()
    {
        $providerName = config('negotiator.debug.performance.providers.current');

        $providerFQN = config("negotiator.debug.performance.providers.available.{$providerName}");

        $this->provider = new $providerFQN();

        $this->trackers = config('negotiator.debug.performance.trackers');
    }

    public function event(string $event, array $data): Event
    {
        return $this->provider->createEvent($event, $data);
    }

    public function trackedEnabled(PerformanceTrackers $tracker): bool
    {
        return $this->trackers[$tracker->value];
    }
}
