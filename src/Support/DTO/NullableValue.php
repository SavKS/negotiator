<?php

namespace Savks\Negotiator\Support\DTO;

use Illuminate\Support\Arr;
use Savks\Negotiator\Exceptions\UnexpectedNull;

use Savks\Negotiator\Support\Types\{
    NullType,
    Types,
    UndefinedType
};

abstract class NullableValue extends Value
{
    public bool $nullable = false;

    public function nullable(): static
    {
        $this->nullable = true;

        return $this;
    }

    public function compile(): mixed
    {
        $value = $this->finalize();

        if ($value === null && ! $this->nullable) {
            throw new UnexpectedNull('NOT NULL', $value);
        }

        return $value;
    }

    public function compileTypes(): Types
    {
        $types = $this->types();

        if ($this->nullable) {
            $types = [
                ...Arr::wrap($types),

                new NullType(),
                new UndefinedType(),
            ];
        }

        return new Types(
            Arr::wrap($types)
        );
    }
}
