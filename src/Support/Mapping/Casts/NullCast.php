<?php

namespace Savks\Negotiator\Support\Mapping\Casts;

use Savks\Negotiator\Support\TypeGeneration\Types\NullType;

class NullCast extends NullableCast
{
    public bool $nullable = true;

    protected function finalize(mixed $source, array $sourcesTrace): mixed
    {
        return null;
    }

    protected function types(): NullType
    {
        return new NullType();
    }
}
