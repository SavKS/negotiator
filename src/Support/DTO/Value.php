<?php

namespace Savks\Negotiator\Support\DTO;

use Illuminate\Support\Arr;
use Savks\Negotiator\Exceptions\UnexpectedNull;

use Savks\Negotiator\Support\Types\{
    Type,
    Types
};

abstract class Value
{
    use WorkWithAccessor;

    abstract protected function finalize(): mixed;

    abstract protected function types(): Type|Types;

    public function compile(): mixed
    {
        $value = $this->finalize();

        if ($value === null) {
            throw new UnexpectedNull('NOT NULL', $value);
        }

        return $value;
    }

    public function compileTypes(): Types
    {
        return new Types(
            Arr::wrap(
                $this->types()
            )
        );
    }
}
