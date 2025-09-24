<?php

namespace Savks\Negotiator\Support\TypeGeneration;

use Savks\Negotiator\Support\Mapping\Mapper;

class MapperAliases
{
    /**
     * @var array<class-string<Mapper>, string>
     */
    protected array $aliases = [];

    /**
     * @param class-string<Mapper> $mapper
     *
     * @return $this
     */
    public function add(string $mapper, string $alias): static
    {
        $this->aliases[$mapper] = $alias;

        return $this;
    }

    /**
     * @param class-string<Mapper> $mapper
     */
    public function resolve(string $mapper): ?string
    {
        return $this->aliases[$mapper] ?? null;
    }
}
