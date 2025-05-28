<?php

namespace Savks\Negotiator\Support\Mapping\Casts;

use Savks\Negotiator\Support\TypeGeneration\Types\NullType;
use Savks\Negotiator\Support\TypeGeneration\Types\UndefinedType;

class NullCast extends OptionalCast
{
    public function __construct()
    {
        $this->nullable();
    }

    protected function finalize(mixed $source, array $sourcesTrace): null
    {
        return null;
    }

    protected function types(): NullType|UndefinedType
    {
        if (! $this->optional['asNull']) {
            return new UndefinedType();
        }

        return new NullType();
    }
}
