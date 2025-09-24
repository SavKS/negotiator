<?php

namespace Savks\Negotiator\Performance;

use LogicException;
use Savks\Negotiator\Enums\PerformanceTrackers;

class Performance
{
    protected readonly ?PerformanceProvider $provider;

    /**
     * @var array{ mappers: bool }
     */
    protected readonly array $trackers;

    public function __construct()
    {
        $providerName = config('negotiator.debug.performance.providers.current');

        $providerClass = config("negotiator.debug.performance.providers.available.{$providerName}");

        $this->provider = config('negotiator.debug.enable') ?
            new $providerClass() :
            null;

        $this->trackers = config('negotiator.debug.performance.trackers');
    }

    public function event(string $event, array $data): Event
    {
        if ($this->provider === null) {
            throw new LogicException('Performance tracking not enabled.');
        }

        return $this->provider->createEvent($event, $data);
    }

    public function trackedEnabled(PerformanceTrackers $tracker): bool
    {
        return $this->provider !== null && $this->trackers[$tracker->value];
    }
}
