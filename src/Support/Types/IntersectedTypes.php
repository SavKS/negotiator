<?php

namespace Savks\Negotiator\Support\Types;

class IntersectedTypes
{
    /**
     * @param Type[] $types
     */
    public function __construct(public readonly array $types)
    {
    }
}
