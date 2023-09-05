<?php

namespace Savks\Negotiator\Support\Types;

use Savks\Negotiator\Support\Mapping\Generic;

class AliasType extends Type
{
    /**
     * @param Generic[] $generics
     */
    public function __construct(
        public readonly string $alias,
        public readonly ?array $generics = null,
    ) {
    }
}
