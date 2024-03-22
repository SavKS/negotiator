<?php

namespace Savks\Negotiator\Support\Mapping\Casts;

trait WorkWithOptionalFields
{
    protected function needSkip(mixed $value, Cast $cast): bool
    {
        if ($value !== null) {
            return false;
        }

        $cast = $cast instanceof ScopeCast ? $cast->nestedCast() : $cast;

        if ($cast instanceof OptionalCast) {
            return $cast->optional['value'] && ! $cast->optional['asNull'];
        }

        return false;
    }
}
