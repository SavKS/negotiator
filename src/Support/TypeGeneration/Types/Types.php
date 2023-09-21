<?php

namespace Savks\Negotiator\Support\TypeGeneration\Types;

class Types
{
    /**
     * @param Type[] $types
     */
    public function __construct(
        public readonly array $types,
        public readonly bool $asIntersection = false
    ) {
    }
}
