<?php

namespace Savks\Negotiator\Support\Types;

use Savks\Negotiator\Support\DTO\Value;

class ConstRecordType extends Type
{
    /**
     * @var array<string, Value>
     */
    public array $props = [];

    public function add(string $key, Type|Types $type): static
    {
        $this->props[$key] = $type;

        return $this;
    }
}
