<?php

namespace Savks\Negotiator\Support\Mapping\Casts;

use LogicException;
use Savks\Negotiator\Exceptions\UnexpectedValue;
use Savks\Negotiator\Support\TypeGeneration\Types\Types;
use stdClass;

class IntersectionCast extends Cast
{
    public readonly array $casts;

    public function __construct(Cast ...$objects)
    {
        $this->casts = $objects;
    }

    protected function finalize(mixed $source, array $sourcesTrace): stdClass|array
    {
        $result = null;

        $initialResultType = null;

        foreach ($this->casts as $cast) {
            $objectResult = $cast->resolve($source, $sourcesTrace);

            if ($objectResult === null) {
                continue;
            }

            if (
                ! is_array($objectResult)
                && (
                    ! is_object($objectResult)
                    || ! ($objectResult instanceof stdClass)
                )
            ) {
                throw new UnexpectedValue(['stdClass', 'array<string, mixed>'], $objectResult);
            }

            if (is_array($objectResult) && ! $objectResult) {
                continue;
            }

            $currentResultType = is_object($objectResult) || ! array_is_list($objectResult) ? 'object' : 'list';

            if (! $initialResultType) {
                $initialResultType = $currentResultType;

                $result = match ($initialResultType) {
                    'object' => new stdClass(),
                    'list' => [],
                };
            } elseif ($initialResultType !== $currentResultType) {
                throw new LogicException('Intersection cannot be performed with different result data types such as list and object (associative array).');
            }

            if ($initialResultType === 'object') {
                foreach ((array)$objectResult as $objectResultKey => $objectResultValue) {
                    $result->{$objectResultKey} = $objectResultValue;
                }
            } else {
                $result = [...$result, ...$objectResult];
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
