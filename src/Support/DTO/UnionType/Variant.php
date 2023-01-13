<?php

namespace Savks\Negotiator\Support\DTO\UnionType;

use Closure;
use Savks\Negotiator\Support\DTO\Castable;

class Variant extends Castable
{
    protected Closure $condition;

    public function if(Closure $condition): static
    {
        $this->condition = $condition;

        return $this;
    }
}
