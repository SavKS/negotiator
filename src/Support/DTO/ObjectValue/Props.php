<?php

namespace Savks\Negotiator\Support\DTO\ObjectValue;

use Closure;

use Savks\Negotiator\Support\DTO\{
    AnyValue,
    HasCasts
};

class Props
{
    use HasCasts;

    public function __construct(protected readonly mixed $source)
    {
    }

    public function when(bool|Closure $condition, Closure|AnyValue $concrete): AnyValue|MissingValue
    {
        $condition = $condition instanceof Closure ? $condition($this->source) : $condition;

        if ($condition) {
            return $concrete instanceof AnyValue ? $concrete : $concrete($this);
        }

        return new MissingValue();
    }
}
