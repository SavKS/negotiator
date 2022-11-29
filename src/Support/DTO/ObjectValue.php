<?php

namespace Savks\Negotiator\Support\DTO;

use Closure;

use Savks\Negotiator\Exceptions\{
    DTOException,
    UnexpectedFinalValue
};
use Savks\Negotiator\Support\DTO\ObjectValue\{
    MissingValue,
    Props
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
            new Props($value)
        );

        if (! \is_array($mappedValue) || \array_is_list($mappedValue)) {
            throw new UnexpectedFinalValue(
                static::class,
                'array<string, ' . Value::class . '>',
                $value,
                $this->accessor
            );
        }

        $result = [];

        /** @var Value|mixed $fieldValue */
        foreach ($mappedValue as $field => $fieldValue) {
            if ($fieldValue instanceof MissingValue) {
                continue;
            }

            if (! $fieldValue instanceof Value) {
                throw new DTOException(
                    sprintf(
                        'Object field "%s" value must extends "%s", given "%s"',
                        $field,
                        Value::class,
                        \gettype($fieldValue)
                    )
                );
            }

            $result[$field] = $fieldValue->compile();
        }

        return $result;
    }
}
