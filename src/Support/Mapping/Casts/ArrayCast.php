<?php

namespace Savks\Negotiator\Support\Mapping\Casts;

use Closure;
use Savks\Negotiator\Contexts\IterationContext;
use Savks\Negotiator\Exceptions\UnexpectedValue;
use Savks\Negotiator\Support\TypeGeneration\Types\ArrayType;

class ArrayCast extends NullableCast
{
    /**
     * @var Closure(mixed): bool|null
     */
    protected ?Closure $filter = null;

    public function __construct(
        protected readonly Cast $cast,
        protected readonly string|Closure|null $accessor = null
    ) {
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
            throw new UnexpectedValue('iterable', $value);
        }

        $result = [];

        $value = is_array($value) ? $value : iterator_to_array($value);

        foreach (array_values($value) as $index => $item) {
            try {
                $data = (new IterationContext($index))->wrap(
                    fn () => $this->cast->resolve($item, $sourcesTrace)
                );

                if ($this->filter && ! ($this->filter)($data)) {
                    continue;
                }

                $result[] = $data;
            } catch (UnexpectedValue $e) {
                throw UnexpectedValue::wrap($e, $index);
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
