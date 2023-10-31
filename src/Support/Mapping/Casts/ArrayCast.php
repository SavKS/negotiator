<?php

namespace Savks\Negotiator\Support\Mapping\Casts;

use Closure;
use Savks\Negotiator\Contexts\IterationContext;
use Savks\Negotiator\Support\TypeGeneration\Types\ArrayType;
use Throwable;

use Savks\Negotiator\Exceptions\{
    InternalException,
    UnexpectedValue
};

class ArrayCast extends NullableCast
{
    /**
     * @var Closure(mixed): bool|null
     */
    protected ?Closure $filter = null;

    protected bool $skipIfNull = false;

    public function __construct(
        protected readonly Cast $cast,
        protected readonly string|Closure|null $accessor = null
    ) {
    }

    public function skipIfNull(): static
    {
        $this->skipIfNull = true;

        return $this;
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
