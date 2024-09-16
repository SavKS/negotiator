<?php

namespace Savks\Negotiator\Support\TypeGeneration\Types;

class TupleType extends Type
{
    /**
     * @var Types[]
     */
    public readonly array $types;

    /**
     * @param list<Type|Types> $types
     */
    public function __construct(array $types)
    {
        $result = [];

        foreach ($types as $type) {
            $result[] = $type instanceof Type ? new Types([$type]) : $type;
        }

        $this->types = $result;
    }
}
