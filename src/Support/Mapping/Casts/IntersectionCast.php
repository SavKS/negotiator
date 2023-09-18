<?php

namespace Savks\Negotiator\Support\Mapping\Casts;

use Savks\Negotiator\Support\TypeGeneration\Types\Types;

class IntersectionCast extends Cast
{
    public readonly array $objects;

    public function __construct(Cast ...$objects)
    {
        $this->objects = $objects;
    }

    protected function finalize(mixed $source, array $sourcesTrace): array
    {
        $result = [];

        foreach ($this->objects as $object) {
            $objectResult = $object->resolve($source, $sourcesTrace);

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
            $types[] = $object->compileTypes()->types;
        }

        return new Types(
            array_merge(...$types),
            true
        );
    }
}
