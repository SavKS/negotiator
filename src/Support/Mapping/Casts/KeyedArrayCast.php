<?php

namespace Savks\Negotiator\Support\Mapping\Casts;

use Closure;
use Savks\Negotiator\Contexts\IterationContext;
use Savks\Negotiator\Support\TypeGeneration\Types\RecordType;
use stdClass;
use Throwable;

use Savks\Negotiator\Exceptions\{
    InternalException,
    UnexpectedValue
};

class KeyedArrayCast extends NullableCast
{
    protected string|Closure|null $keyBy = null;

    protected bool $nullIfEmpty = false;

    public function __construct(
        protected readonly Cast $cast,
        protected readonly string|Closure|null $accessor = null
    ) {
    }

    public function keyBy(string|Closure $accessor): static
    {
        $this->keyBy = $accessor;

        return $this;
    }

    public function nullIfEmpty(): static
    {
        $this->nullIfEmpty = true;
        $this->nullable = true;

        return $this;
    }

    protected function finalize(mixed $source, array $sourcesTrace): ?stdClass
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
            throw new UnexpectedValue('iterable', $value);
        }

        $result = new stdClass();

        $index = 0;

        $hasValues = false;

        foreach ($value as $key => $item) {
            if (! $this->keyBy) {
                $keyValue = (string)$key;
            } else {
                if (is_string($this->keyBy)) {
                    $keyValue = data_get($item, $this->keyBy);
                } else {
                    $keyValue = ($this->keyBy)(
                        $item,
                        $key,
                        ...array_reverse($sourcesTrace)
                    );
                }
            }

            if (! is_string($keyValue)) {
                throw new UnexpectedValue('string', $keyValue);
            }

            try {
                $result->{$keyValue} = (new IterationContext($index++, $key))->wrap(
                    fn () => $this->cast->resolve($item, $sourcesTrace)
                );

                $hasValues = true;
            } catch (UnexpectedValue $e) {
                throw UnexpectedValue::wrap($e, "{$key}({$keyValue})");
            } catch (Throwable $e) {
                throw InternalException::wrap($e, "{$key}({$keyValue})");
            }
        }

        return ! $hasValues && $this->nullIfEmpty ? null : $result;
    }

    protected function types(): RecordType
    {
        return new RecordType(
            valueType: $this->cast->compileTypes()
        );
    }
}
