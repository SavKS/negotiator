<?php

namespace Savks\Negotiator\Support\TypeGeneration\TypeScript;

use Savks\Negotiator\Exceptions\TypeGenerateException;

use Savks\Negotiator\Support\Mapping\{
    Casts\Cast,
    Mapper
};

class Target
{
    /**
     * @param array<string, class-string<Mapper>|Cast> $mappersMap
     */
    public function __construct(
        public readonly array $mappersMap,
        public readonly ?string $namespace
    ) {
        foreach ($mappersMap as $mapper) {
            if (! is_subclass_of($mapper, Mapper::class)
                && ! ($mapper instanceof Cast)
            ) {
                throw new TypeGenerateException('Invalid mappers map.');
            }
        }
    }
}
