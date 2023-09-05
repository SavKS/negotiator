<?php

namespace Savks\Negotiator\Performance;

use Closure;

class Event
{
    protected readonly bool $debug;

    public function __construct(
        protected readonly Closure $begin,
        protected readonly Closure $end
    ) {
        $this->debug = config('negotiator.debug.enable') ?? config('app.debug');
    }

    public function begin(): void
    {
        if (! $this->debug) {
            return;
        }

        ($this->begin)();
    }

    public function end(): void
    {
        if (! $this->debug) {
            return;
        }

        ($this->end)();
    }

    public function wrap(Closure $closure): mixed
    {
        $this->begin();

        $result = $closure();

        $this->end();

        return $result;
    }
}
