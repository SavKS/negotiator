<?php

namespace Savks\Negotiator\Support\Mapping\Casts;

trait CanBeGeneric
{
    protected ?string $assignedToGeneric = null;

    public function asGeneric(string $name): static
    {
        $this->assignedToGeneric = $name;

        return $this;
    }
}
