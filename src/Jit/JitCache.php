<?php

namespace Savks\Negotiator\Jit;

use Closure;

class JitCache
{
    protected array $cache = [];

    public function resolve(Jit $jit, Closure $callback): array
    {
        if (! isset($this->cache[$jit->uid])) {
            $this->cache[$jit->uid] = $callback();
        }

        return $this->cache[$jit->uid];
    }
}
