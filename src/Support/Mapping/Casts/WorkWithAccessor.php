<?php

namespace Savks\Negotiator\Support\Mapping\Casts;

use Closure;

trait WorkWithAccessor
{
    protected static function resolveValueFromAccessor(
        string|Closure|null $accessor,
        mixed $source,
        array $sourcesTrace
    ): mixed {
        return match (true) {
            $accessor === null => $source,

            is_string($accessor) => data_get($source, $accessor),

            default => $accessor(
                $source,
                ...array_reverse($sourcesTrace)
            )
        };
    }
}
