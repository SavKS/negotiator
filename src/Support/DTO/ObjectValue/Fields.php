<?php

namespace Savks\Negotiator\Support\DTO\ObjectValue;

use Closure;

use Savks\Negotiator\Support\DTO\{
    AnyValue,
    HasCasts
};

class Fields
{
    use HasCasts;

    public function __construct(protected readonly mixed $source)
    {
    }

    public function when(bool $condition, Closure|AnyValue $concrete): AnyValue|MissingValue
    {
        if ($condition) {
            return $concrete instanceof AnyValue ? $concrete : $concrete($this);
        }

        return new MissingValue();
    }
}
