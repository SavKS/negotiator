<?php

namespace Savks\Negotiator\Support\DTO;

trait CanBeGeneric
{
    protected ?string $assignedToGeneric = null;

    public function asGeneric(string $name): static
    {
        $this->assignedToGeneric = $name;

        return $this;
    }
}
