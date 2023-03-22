<?php

namespace Savks\Negotiator\TypeGeneration;

use Savks\Negotiator\Support\Mapping\Mapper;
use Savks\Negotiator\Support\Types\AliasType;

use Savks\Negotiator\Support\DTO\{
    NullableValue,
    Value
};

class AliasMapper extends Mapper
{
    public function __construct(public readonly string $alias)
    {
    }

    public function map(): Value
    {
        return new class ($this->alias) extends NullableValue {
            public function __construct(public readonly string $alias)
            {
            }

            protected function finalize(): mixed
            {
                return null;
            }

            protected function types(): AliasType
            {
                return new AliasType($this->alias);
            }
        };
    }
}
