<?php

namespace Savks\Negotiator\Support\Mapping\Casts;

use Closure;

use function Savks\Negotiator\resolve_value_from_accessor;

trait WorkWithAccessor
{
    protected static function resolveValueFromAccessor(
        string|Closure|null $accessor,
        mixed $source,
        array $sourcesTrace
    ): mixed {
        return resolve_value_from_accessor($accessor, [
            $source,
            ...array_reverse($sourcesTrace),
        ]);
    }
}
