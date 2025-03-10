<?php

namespace Savks\Negotiator\Support\Mapping\Casts;

use Closure;
use Savks\Negotiator\Contexts\IterationContext;
use Savks\Negotiator\Enums\OptionalModes;
use Savks\Negotiator\Exceptions\InternalException;
use Savks\Negotiator\Exceptions\UnexpectedValue;
use Savks\Negotiator\Support\TypeGeneration\Types\ArrayType;
use stdClass;
use Throwable;

class ArrayCast extends OptionalCast
{
    /**
     * @var Closure(mixed): bool|null
     */
    protected ?Closure $filter = null;

    protected bool $skipIfNull = false;

    protected bool $stdClassCastAllowed = false;

    public function __construct(
        protected readonly Cast $cast,
        protected readonly string|Closure|null $accessor = null
    ) {
    }

    public function allowCastStdClass(): static
    {
        $this->stdClassCastAllowed = true;

        return $this;
    }

    public function skipIfNull(): static
    {
        $this->skipIfNull = true;

        return $this;
    }

    public function optionalIfEmpty(): static
    {
        return $this->optional(OptionalModes::EMPTY_ARRAY_AS_OPTIONAL);
    }

    /**
     * @param Closure(mixed): bool $closure
     */
    public function filter(Closure $closure): static
    {
        $this->filter = $closure;

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

        if (! is_iterable($value)) {
            if ($this->stdClassCastAllowed) {
                if (! ($value instanceof stdClass)) {
                    throw new UnexpectedValue(['stdClass', 'iterable'], $value);
                }
            } else {
                throw new UnexpectedValue('iterable', $value);
            }
        }

        $result = [];

        if ($value instanceof stdClass) {
            $value = (array)$value;
        } else {
            $value = is_array($value) ? $value : iterator_to_array($value);
        }

        $index = -1;

        foreach ($value as $key => $item) {
            $index++;

            if ($this->skipIfNull && $item === null) {
                continue;
            }

            try {
                $data = (new IterationContext($index, $key))->wrap(
                    fn () => $this->cast->resolve($item, $sourcesTrace)
                );

                if ($this->filter && ! ($this->filter)($data)) {
                    continue;
                }

                $result[] = $data;
            } catch (UnexpectedValue $e) {
                throw UnexpectedValue::wrap($e, $index);
            } catch (Throwable $e) {
                throw InternalException::wrap($e, $index);
            }
        }

        return $result;
    }

    protected function types(): ArrayType
    {
        return new ArrayType(
            $this->cast->compileTypes()
        );
    }
}
