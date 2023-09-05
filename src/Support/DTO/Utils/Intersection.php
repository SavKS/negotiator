<?php

namespace Savks\Negotiator\Support\DTO\Utils;

use Savks\Negotiator\Contexts\TypeGenerationContext;
use Savks\Negotiator\Support\Mapping\Mapper;
use Savks\Negotiator\TypeGeneration\MapperAliases;

use Savks\Negotiator\Support\DTO\{
    MapperValue,
    Value
};
use Savks\Negotiator\Support\Types\{
    AliasType,
    Types
};

class Intersection extends Value
{
    public readonly array $objects;

    public function __construct(Value|Mapper ...$objects)
    {
        $this->objects = $objects;
    }

    protected function finalize(): array
    {
        $result = [];

        foreach ($this->objects as $object) {
            $normalizedObject = match (true) {
                $object instanceof Mapper => $object->map(),
                $object instanceof MapperValue => $object->resolveMapper()->map(),

                default => $object
            };

            $objectResult = $normalizedObject->compile();

            if ($objectResult !== null) {
                $result[] = $objectResult;
            }
        }

        return array_merge(...$result);
    }

    protected function types(): Types
    {
        $types = [];

        foreach ($this->objects as $object) {
            if ($object instanceof Mapper) {
                $alias = app(MapperAliases::class)->resolve($object::class);

                if (! $alias) {
                    $alias = TypeGenerationContext::useSelf()->resolveMapperRef($object::class);
                }

                $types[] = $alias ?
                    [new AliasType($alias)] :
                    $object->map()->compileTypes()->types;
            } else {
                $types[] = $object->compileTypes()->types;
            }
        }

        return new Types(
            array_merge(...$types),
            true
        );
    }
}
