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
    public function __construct(
        protected readonly mixed $source,
        protected readonly string|Closure $iterator,
        protected readonly string|Closure|null $accessor = null
    ) {
    }

    protected function finalize(): mixed
    {
        if ($this->accessor === null) {
            $value = $this->source;
        } elseif (\is_string($this->accessor)) {
            $value = \data_get($this->source, $this->accessor);
        } else {
            $value = ($this->accessor)($this->source);
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
                new Item($item)
            );

            if (! $listItemValue instanceof Value) {
                throw new UnexpectedValue(Value::class, $listItemValue, $index);
            }

            try {
                $result[] = $listItemValue->compile();
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
