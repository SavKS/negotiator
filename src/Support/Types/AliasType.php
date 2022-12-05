<?php

namespace Savks\Negotiator\Support\Types;

class AliasType extends Type
{
    public function __construct(public readonly string $alias)
    {
    }
}
