<?php

namespace Savks\Negotiator\Support\DTO\ObjectValue;

use Closure;

use Savks\Negotiator\Support\DTO\{
    HasCasts,
    Value
};

class Props
{
    use HasCasts;

    public function __construct(protected readonly mixed $source)
    {
    }

    public function when(bool|Closure $condition, Closure|Value $concrete): Value|MissingValue
    {
        $condition = $condition instanceof Closure ? $condition($this->source) : $condition;

        if ($condition) {
            return $concrete instanceof Value ? $concrete : $concrete($this);
        }

        return new MissingValue();
    }
}
