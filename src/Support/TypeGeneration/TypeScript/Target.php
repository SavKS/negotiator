<?php

namespace Savks\Negotiator\Support\TypeGeneration\TypeScript;

use Savks\Negotiator\Exceptions\TypeGenerateException;
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
        foreach ($mappersMap as $mapperFQN) {
            if (! is_subclass_of($mapperFQN, Mapper::class)) {
                throw new TypeGenerateException('Invalid mappers map.');
            }
        }
    }
}
