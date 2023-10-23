<?php

namespace Savks\Negotiator\Support\Mapping\Casts;

use Savks\Negotiator\Support\TypeGeneration\Types\Types;
use stdClass;

class IntersectionCast extends Cast
{
    public readonly array $casts;

    public function __construct(Cast ...$objects)
    {
        $this->casts = $objects;
    }

    protected function finalize(mixed $source, array $sourcesTrace): stdClass
    {
        $result = new stdClass();

        foreach ($this->casts as $cast) {
            $objectResult = $cast->resolve($source, $sourcesTrace);

            if ($objectResult !== null) {
                foreach ((array)$objectResult as $objectResultKey => $objectResultValue) {
                    $result->{$objectResultKey} = $objectResultValue;
                }
            }
        }

        return $result;
    }

    protected function types(): Types
    {
        $types = [];

        foreach ($this->casts as $object) {
            $types[] = $object->compileTypes()->types;
        }

        return new Types(
            array_merge(...$types),
            true
        );
    }
}
