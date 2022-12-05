<?php

namespace Savks\Negotiator\Support\DTO;

use Closure;
use Savks\Negotiator\Exceptions\UnexpectedValue;
use Savks\Negotiator\Support\DTO\ObjectValue\MissingValue;
use Savks\Negotiator\Support\DTO\Utils\Factory;

use Savks\Negotiator\Support\Types\{
    ConstRecordType,
    Type,
    Types
};

class ObjectValue extends Value
{
    /**
     * @var array<string, Value>|null
     */
    protected ?array $value;

    public function __construct(
        protected readonly mixed $source,
        protected readonly Closure $callback,
        protected readonly string|Closure|null $accessor = null
    ) {
    }

    protected function finalize(): ?array
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

        $mappedValue = ($this->callback)(
            new Factory($value)
        );

        if (! \is_array($mappedValue) || \array_is_list($mappedValue)) {
            throw new UnexpectedValue('array<string, ' . Value::class . '>', $mappedValue);
        }

        $result = [];

        /** @var Value|mixed $fieldValue */
        foreach ($mappedValue as $field => $fieldValue) {
            if ($fieldValue instanceof MissingValue) {
                continue;
            }

            if (! $fieldValue instanceof Value) {
                throw new UnexpectedValue(Value::class, $fieldValue);
            }

            try {
                $result[$field] = $fieldValue->compile();
            } catch (UnexpectedValue $e) {
                throw UnexpectedValue::wrap($e, $field);
            }
        }

        return $result;
    }

    protected function types(): ConstRecordType
    {
        /** @var array<string, Value> $mappedValue */
        $mappedValue = ($this->callback)(
            new Factory(null)
        );

        $result = new ConstRecordType();

        foreach ($mappedValue as $field => $value) {
            $result->add(
                $field,
                $value->compileTypes()
            );
        }

        return $result;
    }
}
