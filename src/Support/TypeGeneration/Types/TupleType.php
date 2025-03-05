<?php

namespace Savks\Negotiator\Support\TypeGeneration\Types;

use Illuminate\Support\Arr;

class TupleType extends Type
{
    /**
     * @var Types[]
     */
    public readonly array $types;

    /**
     * @param list<Type[]> $types
     */
    public function __construct(
        array $types,
        public readonly ?Types $restType = null
    ) {
        $this->types = Arr::map(
            $types,
            fn (array $type) => new Types($type)
        );
    }
}
