<?php

namespace Savks\Negotiator\Contexts;

use Closure;
use Savks\Negotiator\Enums\RefTypes;
use Savks\Negotiator\Support\Mapping\Mapper;
use Savks\PhpContexts\Context;

class TypeGenerationContext extends Context
{
    /**
     * @param (Closure(RefTypes, class-string<Mapper>): ?string)|null $refsResolver
     */
    public function __construct(protected readonly ?Closure $refsResolver = null)
    {
    }

    public function resolveMapperRef(string $mapperFQN): ?string
    {
        return ($this->refsResolver)(RefTypes::MAPPER, $mapperFQN);
    }

    public function resolveEnumRef(string $enumFQN)
    {
        return ($this->refsResolver)(RefTypes::ENUM, $enumFQN);
    }
}
