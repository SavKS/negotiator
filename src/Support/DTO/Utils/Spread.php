<?php

namespace Savks\Negotiator\Support\DTO\Utils;

use Closure;
use Savks\Negotiator\Exceptions\UnexpectedValue;
use Savks\Negotiator\Support\Types\ConstRecordType;

use Savks\Negotiator\Support\DTO\{
    ObjectValue\MissingValue,
    Value,
    WorkWithAccessor
};

class Spread
{
    use WorkWithAccessor;

    /**
     * @param Closure(Factory): array $callback
     */
    public function __construct(
        protected readonly mixed $source,
        protected readonly Closure $callback,
        protected readonly string|Closure|null $accessor = null
    ) {
    }

    public function applyTo(array &$data): void
    {
        $value = $this->resolveValueFromAccessor($this->accessor, $this->source);

        $factory = new Factory($value);

        $mappedValue = ($this->callback)($factory);

        /** @var Value|Spread|mixed $fieldValue */
        foreach ($mappedValue as $field => $fieldValue) {
            if ($fieldValue instanceof Spread) {
                $fieldValue->applyTo($data);
            } else {
                if ($fieldValue instanceof MissingValue) {
                    continue;
                }

                if (! $fieldValue instanceof Value) {
                    throw new UnexpectedValue(Value::class, $fieldValue);
                }

                try {
                    $data[$field] = $fieldValue->compile();
                } catch (UnexpectedValue $e) {
                    throw UnexpectedValue::wrap($e, $field);
                }
            }
        }
    }

    public function applyTypesTo(ConstRecordType $resultType): void
    {
        /** @var array<string, Value|Spread> $mappedValue */
        $mappedValue = ($this->callback)(
            new Factory(null)
        );

        foreach ($mappedValue as $field => $value) {
            if ($value instanceof Spread) {
                $value->applyTypesTo($resultType);
            } else {
                $resultType->add(
                    $field,
                    $value->compileTypes()
                );
            }
        }
    }
}
