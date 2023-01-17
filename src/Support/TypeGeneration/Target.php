<?php

namespace Savks\Negotiator\Support\TypeGeneration;

use Savks\Negotiator\Support\Mapping\Mapper;

class Target
{
    /**
     * @param array<string, class-string<Mapper>> $mappersMap
     */
    public function __construct(
        public readonly array $mappersMap,
        public readonly ?string $namespace
    ) {
    }
}
