<?php

namespace Savks\Negotiator\Support\DTO\Utils;

use Closure;

use Savks\Negotiator\Support\DTO\{
    ObjectValue\MissingValue,
    HasCasts,
    Value
};

class Factory
{
    use HasCasts;

    public function __construct(protected readonly mixed $source)
    {
    }

    public function when(
        bool|Closure $condition,
        Closure|Value $concrete,
        Closure|Value|null $else = null
    ): Value|MissingValue {
        $condition = $condition instanceof Closure ? $condition($this->source) : $condition;

        if ($condition) {
            return $concrete instanceof Value ? $concrete : $concrete($this);
        }

        if ($else) {
            return $else instanceof Value ? $else : $else($this);
        }

        return new MissingValue();
    }
}
