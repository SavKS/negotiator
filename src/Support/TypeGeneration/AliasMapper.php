<?php

namespace Savks\Negotiator\Support\TypeGeneration;

use Savks\Negotiator\Support\TypeGeneration\Types\AliasType;

use Savks\Negotiator\Support\Mapping\{
    Casts\Cast,
    Casts\OptionalCast,
    Mapper
};

final class AliasMapper extends Mapper
{
    public static string $alias;

    private function __construct()
    {
    }

    public static function schema(): Cast
    {
        return new class(self::$alias) extends OptionalCast
        {
            public function __construct(public readonly string $alias)
            {
            }

            protected function finalize(mixed $source, array $sourcesTrace): mixed
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
