<?php

namespace Savks\Negotiator\Support\Types;

class ConstBooleanType extends Type
{
    public function __construct(public readonly bool $value)
    {
    }
}
