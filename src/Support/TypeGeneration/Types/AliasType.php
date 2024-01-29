<?php

namespace Savks\Negotiator\Support\TypeGeneration\Types;

use Savks\Negotiator\Enums\RefTypes;
use Savks\Negotiator\Support\Mapping\Generic;

class AliasType extends Type
{
    /**
     * @param Generic[] $generics
     * @param array{
     *     type: RefTypes,
     *     fqn: class-string
     * }|null $ref
     */
    public function __construct(
        public readonly string $alias,
        public readonly ?array $generics = null,
        public readonly ?array $ref = null
    ) {
    }
}
