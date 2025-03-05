<?php

namespace Savks\Negotiator\Support\Mapping\Casts;

use Closure;
use LogicException;
use Savks\Negotiator\Exceptions\InternalException;
use Savks\Negotiator\Exceptions\UnexpectedValue;
use Savks\Negotiator\Support\Mapping\Mapper;
use Savks\Negotiator\Support\TypeGeneration\Types\TupleType;
use stdClass;
use Throwable;

class TupleCast extends OptionalCast
{
    /**
     * @var array{
     *     cast: Cast,
     *     accessor: string|Closure|null
     * }|null
     */
    protected ?array $rest = null;

    /**
     * @param list<Cast|Mapper> $casts
     */
    public function __construct(
        public readonly array $casts,
        protected readonly string|Closure|null $accessor = null
    ) {
    }

    public function rest(Cast $cast, string|Closure|null $accessor = null): static
    {
        $this->rest = [
            'cast' => $cast,
            'accessor' => $accessor,
        ];

        return $this;
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
                $result[] = $cast->resolve($value, $sourcesTrace);
            } catch (UnexpectedValue $e) {
                throw UnexpectedValue::wrap($e, $index);
            } catch (Throwable $e) {
                throw InternalException::wrap($e, $index);
            }
        }

        if ($this->rest) {
            $restValues = $this->rest['accessor']
                ? static::resolveValueFromAccessor(
                    $this->rest['accessor'],
                    $value,
                    $sourcesTrace
                )
                : $value;

            $restSourcesTrace = $sourcesTrace;

            if ($this->rest['accessor'] && last($restSourcesTrace) !== $source) {
                $restSourcesTrace[] = $source;
            }

            if (! is_iterable($value) && ! ($value instanceof stdClass)) {
                throw new LogicException('The value for Rest must be an iterable of stdClass.');
            }

            if ($restValues instanceof stdClass) {
                $restValues = (array)$restValues;
            } else {
                $restValues = is_array($restValues) ? $restValues : iterator_to_array($restValues);
            }

            foreach ($restValues as $restIndex => $restValue) {
                try {
                    $result[] = $this->rest['cast']->resolve($restValue, $restSourcesTrace);
                } catch (UnexpectedValue $e) {
                    throw UnexpectedValue::wrap($e, $restIndex);
                } catch (Throwable $e) {
                    throw InternalException::wrap($e, $restIndex);
                }
            }
        }

        return $result;
    }

    protected function types(): TupleType
    {
        $types = [];

        foreach ($this->casts as $cast) {
            $types[] = $cast->compileTypes()->types;
        }

        return new TupleType(
            $types,
            $this->rest ? $this->rest['cast']->compileTypes() : null
        );
    }
}
