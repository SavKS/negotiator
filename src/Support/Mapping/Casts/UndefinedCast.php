<?php

namespace Savks\Negotiator\Support\Mapping\Casts;

use Savks\Negotiator\Support\TypeGeneration\Types\UndefinedType;

class UndefinedCast extends OptionalCast
{
    public function __construct()
    {
        $this->optional();
    }

    protected function finalize(mixed $source, array $sourcesTrace): null
    {
        return null;
    }

    protected function types(): UndefinedType
    {
        return new UndefinedType();
    }
}
