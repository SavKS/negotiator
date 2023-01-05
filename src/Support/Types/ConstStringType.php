<?php

namespace Savks\Negotiator\Support\Types;

class ConstStringType extends Type
{
    public function __construct(public readonly string $value)
    {
    }
}
