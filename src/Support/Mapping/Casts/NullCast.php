<?php

namespace Savks\Negotiator\Support\Mapping\Casts;

use Savks\Negotiator\Support\TypeGeneration\Types\NullType;

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

    protected function types(): NullType
    {
        return new NullType();
    }
}
