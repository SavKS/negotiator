<?php

namespace Savks\Negotiator\Support\TypeGeneration\Types;

class ConstBooleanType extends Type
{
    public function __construct(public readonly bool $value)
    {
    }
}
