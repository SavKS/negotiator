<?php

namespace Savks\Negotiator\Support\Mapping\Casts;

use Closure;
use Illuminate\Support\Arr;
use Savks\Negotiator\Support\Mapping\Mapper;
use Savks\Negotiator\Support\TypeGeneration\Types\TupleType;
use Throwable;

use Savks\Negotiator\Exceptions\{
    InternalException,
    UnexpectedValue
};

class TupleCast extends OptionalCast
{
    /**
     * @param list<Cast|Mapper> $casts
     */
    public function __construct(
        public readonly array $casts,
        protected readonly string|Closure|null $accessor = null
    ) {
    }

    protected function finalize(mixed $source, array $sourcesTrace): ?array
    {
        $value = static::resolveValueFromAccessor(
            $this->accessor,
            $source,
            $sourcesTrace
        );

        if ($this->accessor && last($sourcesTrace) !== $source) {
            $sourcesTrace[] = $source;
        }

        if ($value === null) {
            return null;
        }

        $result = [];

        foreach (array_values($this->casts) as $index => $cast) {
            try {
                $result[] = $cast->resolve($source, $sourcesTrace);
            } catch (UnexpectedValue $e) {
                throw UnexpectedValue::wrap($e, $index);
            } catch (Throwable $e) {
                throw InternalException::wrap($e, $index);
            }
        }

        return $result;
    }

    protected function types(): TupleType
    {
        $types = [];

        foreach ($this->casts as $object) {
            $types[] = $object->compileTypes()->types;
        }

        return new TupleType(
            Arr::collapse($types)
        );
    }
}
