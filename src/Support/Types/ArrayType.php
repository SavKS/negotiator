<?php

namespace Savks\Negotiator\Support\Types;

class ArrayType extends Type
{
    public readonly Types $types;

    public function __construct(Type|Types $type)
    {
        $this->types = $type instanceof Type ? new Types([$type]) : $type;
    }
}
