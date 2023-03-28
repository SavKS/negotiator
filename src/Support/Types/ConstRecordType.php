<?php

namespace Savks\Negotiator\Support\Types;

class ConstRecordType extends Type
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
