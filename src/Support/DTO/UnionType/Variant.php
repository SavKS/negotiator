<?php

namespace Savks\Negotiator\Support\DTO\UnionType;

use Closure;

use Savks\Negotiator\Support\DTO\{
    HasCasts,
    Value
};

class Variant
{
    use HasCasts;

    /**
     * @var Closure(mixed): Value|null
     */
    protected Closure $condition;

    /**
     * @var Closure(mixed): Value|null
     */
    public function if(Closure $condition): static
    {
        $this->condition = $condition;

        return $this;
    }
}
