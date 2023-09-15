<?php

namespace Savks\Negotiator\TypeGeneration;

use Savks\Negotiator\Support\Mapping\Mapper;
use Savks\Negotiator\Support\Types\AliasType;

use Savks\Negotiator\Support\DTO\{
    Cast,
    NullableCast
};

class AliasMapper extends Mapper
{
    public function __construct(public readonly string $alias)
    {
    }

    public function schema(): Cast
    {
        return new class ($this->alias) extends NullableCast {
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
