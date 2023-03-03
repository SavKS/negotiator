<?php

namespace Savks\Negotiator\Support\DTO;

use Closure;
use Savks\Negotiator\Exceptions\UnexpectedValue;
use Savks\Negotiator\Support\DTO\ArrayValue\Item;

use Savks\Negotiator\Support\Types\{
    ArrayType,
    Type,
    Types
};

class ArrayValue extends NullableValue
{
    /**
     * @var (Closure(mixed): bool)|null
     */
    protected ?Closure $filter = null;

    public function __construct(
        protected readonly mixed $source,
        protected readonly string|Closure $iterator,
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

    protected function finalize(): mixed
    {
        $value = $this->resolveValueFromAccessor(
            $this->accessor,
            $this->source,
            $this->sourcesTrace
        );

        if ($this->accessor && last($this->sourcesTrace) !== $this->source) {
            $this->sourcesTrace[] = $this->source;
        }

        if ($value === null) {
            return null;
        }

        if (! \is_iterable($value)) {
            throw new UnexpectedValue('iterable', $value);
        }

        $result = [];

        foreach ($value as $index => $item) {
            $listItemValue = ($this->iterator)(
                new Item($item, $this->sourcesTrace)
            );

            if (! $listItemValue instanceof Value) {
                throw new UnexpectedValue(Value::class, $listItemValue, $index);
            }

            try {
                $data = $listItemValue->compile();

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

    protected function types(): Type|Types
    {
        /** @var Value $value */
        $value = ($this->iterator)(
            new Item(null)
        );

        return new ArrayType(
            $value->compileTypes()
        );
    }
}
