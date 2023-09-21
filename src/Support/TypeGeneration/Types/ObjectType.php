<?php

namespace Savks\Negotiator\Support\TypeGeneration\Types;

class ObjectType extends Type
{
    /**
     * @var array<string|int, Type|Types>
     */
    public array $props = [];

    public function add(string|int $key, Type|Types $type): static
    {
        $this->props[$key] = $type;

        return $this;
    }
}
