<?php

namespace Savks\Negotiator\Support\DTO\Utils;

use Closure;
use Illuminate\Support\Arr;
use Savks\Negotiator\Contexts\TypeGenerationContext;
use Savks\PhpContexts\Context;

use Savks\Negotiator\Support\DTO\{
    ObjectValue\MissingValue,
    HasCasts,
    Value
};
use Savks\Negotiator\Support\Types\{
    Type,
    Types
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
    ): Value|MissingValue|Type|Types {
        $typeGenerationContext = Context::tryUse(TypeGenerationContext::class);

        if ($typeGenerationContext) {
            $value = $concrete instanceof Closure ? $concrete($this) : $concrete;

            if (! $else) {
                return $value;
            }

            $elseValue = $else instanceof Closure ? $else($this) : $else;

            return new Types([
                ...Arr::wrap($value),
                ...Arr::wrap($elseValue),
            ]);
        }

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
