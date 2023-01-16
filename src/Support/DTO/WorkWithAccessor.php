<?php

namespace Savks\Negotiator\Support\DTO;

use Closure;

trait WorkWithAccessor
{
    protected function resolveValueFromAccessor(string|Closure|null $accessor, mixed $source): mixed
    {
        return match (true) {
            $accessor === null => $source,

            \is_string($accessor) => \data_get($source, $accessor),

            default => $accessor($source)
        };
    }
}
