<?php

namespace Savks\Negotiator\Support\Mapping\Casts;

use Closure;
use Savks\Negotiator\Contexts\IterationContext;
use Savks\Negotiator\Exceptions\UnexpectedValue;
use Savks\Negotiator\Support\TypeGeneration\Types\RecordType;

class KeyedArrayCast extends NullableCast
{
    protected string|Closure|null $keyBy = null;

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
            throw new UnexpectedValue('iterable', $value);
        }

        $result = [];

        $index = 0;

        foreach ($value as $key => $item) {
            if (! $this->keyBy) {
                $keyValue = $key;
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
                $result[$keyValue] = (new IterationContext($index++))->wrap(
                    fn () => $this->cast->resolve($item, $sourcesTrace)
                );
            } catch (UnexpectedValue $e) {
                throw UnexpectedValue::wrap($e, "{$key}({$keyValue})");
            }
        }

        return $result ?: null;
    }

    protected function types(): RecordType
    {
        return new RecordType(
            valueType: $this->cast->compileTypes()
        );
    }
}
