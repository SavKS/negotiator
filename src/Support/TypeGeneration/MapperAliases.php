<?php

namespace Savks\Negotiator\Support\TypeGeneration;

use Savks\Negotiator\Support\Mapping\Mapper;

class MapperAliases
{
    /**
     * @var array<class-string<Mapper>, string>
     */
    protected array $aliases = [];

    public function add(string $mapperFQN, string $alias): static
    {
        $this->aliases[$mapperFQN] = $alias;

        return $this;
    }

    public function resolve(string $mapperFQN): ?string
    {
        return $this->aliases[$mapperFQN] ?? null;
    }
}
