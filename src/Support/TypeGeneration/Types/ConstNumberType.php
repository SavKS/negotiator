<?php

namespace Savks\Negotiator\Support\TypeGeneration\Types;

class ConstNumberType extends Type
{
    public function __construct(public readonly int|float $value)
    {
    }
}
