<?php

namespace Savks\Negotiator\Contexts;

use BackedEnum;
use Closure;
use Savks\Negotiator\Enums\RefTypes;
use Savks\Negotiator\Support\Mapping\Mapper;
use Savks\PhpContexts\Context;

class TypeGenerationContext extends Context
{
    /**
     * @param (Closure(RefTypes $type, class-string<Mapper>|class-string<BackedEnum> $target): ?string)|null $refsResolver
     */
    public function __construct(public readonly ?Closure $refsResolver = null)
    {
    }

    /**
     * @param class-string<Mapper> $mapper
     */
    public function resolveMapperRef(string $mapper): ?string
    {
        if (! $this->refsResolver) {
            return null;
        }

        return ($this->refsResolver)(RefTypes::MAPPER, $mapper);
    }

    /**
     * @param class-string<BackedEnum> $enum
     */
    public function resolveEnumRef(string $enum): ?string
    {
        if (! $this->refsResolver) {
            return null;
        }

        return ($this->refsResolver)(RefTypes::ENUM, $enum);
    }
}
