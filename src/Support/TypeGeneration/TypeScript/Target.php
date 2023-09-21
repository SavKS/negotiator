<?php

namespace Savks\Negotiator\Support\TypeGeneration\TypeScript;

use Closure;
use Savks\Negotiator\Support\Mapping\Mapper;

class Target
{
    /**
     * @param array<string, class-string<Mapper>|(Closure():class-string<Mapper>|Mapper)> $mappersMap
     */
    public function __construct(
        public readonly array $mappersMap,
        public readonly ?string $namespace
    ) {
    }
}
