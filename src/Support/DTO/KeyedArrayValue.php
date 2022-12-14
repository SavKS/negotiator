<?php

namespace Savks\Negotiator\Support\DTO;

use Closure;
use Savks\Negotiator\Support\DTO\ArrayValue\Item;

use Savks\Negotiator\Exceptions\{
        DTOException,
        UnexpectedNull,
        UnexpectedSourceValue,
        UnexpectedValue
};
use Savks\Negotiator\Support\Types\{
        RecordType,
        Type,
        Types
};

class KeyedArrayValue extends NullableValue
{
    public function __construct(
        protected readonly mixed $source,
        protected readonly string|Closure $key,
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
                throw new UnexpectedValue(
                    Value::class,
                    $listItemValue
                );
            }

            if (\is_string($this->key)) {
                $keyValue = \data_get($item, $this->key);
            } else {
                $keyValue = ($this->key)($item);
            }

            if (! \is_string($keyValue)) {
                throw new UnexpectedValue('string', $keyValue);
            }

            try {
                $result[$keyValue] = $listItemValue->compile();
            } catch (UnexpectedValue $e) {
                throw UnexpectedValue::wrap($e, "{$index}({$keyValue})");
            }
        }

        return $result ?: null;
    }

    protected function types(): Type|Types
    {
        /** @var Value $listItem */
        $listItem = ($this->iterator)(
            new Item(null)
        );

        return new RecordType(
            $listItem->compileTypes()
        );
    }
}
