<?php

namespace Savks\Negotiator\Support\Types;

class Types
{
    /**
     * @param Type[] $types
     */
    public function __construct(public readonly array $types)
    {
    }
}
