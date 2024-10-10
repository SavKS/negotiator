<?php

namespace Savks\Negotiator\Support\TypeGeneration\Types;

class TupleType extends Type
{
    /**
     * @var Types[]
     */
    public readonly array $types;

    /**
     * @param list<Type[]> $types
     */
    public function __construct(array $types)
    {
        $result = [];

        foreach ($types as $type) {
            $result[] = new Types($type);
        }

        $this->types = $result;
    }
}
