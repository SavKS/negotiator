<?php

namespace Savks\Negotiator\Support\DTO;

use Closure;
use Savks\Negotiator\Contexts\ObjectIgnoredKeysContext;
use Savks\Negotiator\Exceptions\UnexpectedValue;
use Savks\Negotiator\Support\DTO\ObjectValue\MissingValue;
use Savks\Negotiator\Support\Types\ConstRecordType;
use Savks\PhpContexts\Context;

use Savks\Negotiator\Support\DTO\Utils\{
    Factory,
    Spread
};

class ObjectValue extends NullableValue
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
        $value = $this->resolveValueFromAccessor($this->accessor, $this->source);

        if ($value === null) {
            return null;
        }

        $mappedValue = ($this->callback)(
            new Factory($value)
        );

        if (! \is_array($mappedValue) || \array_is_list($mappedValue)) {
            throw new UnexpectedValue('array<string, ' . Value::class . '>', $mappedValue);
        }

        /** @var ObjectIgnoredKeysContext|null $intersectContext */
        $intersectContext = Context::tryUse(ObjectIgnoredKeysContext::class);

        $result = [];

        /** @var Value|Merge|mixed $fieldValue */
        foreach ($mappedValue as $field => $fieldValue) {
            if ($fieldValue instanceof Spread) {
                $fieldValue->applyTo($result);
            } else {
                if ($intersectContext?->includes($field)) {
                    continue;
                }

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
        }

        return $result;
    }

    protected function types(): ConstRecordType
    {
        /** @var array<string, Value|Spread> $mappedValue */
        $mappedValue = ($this->callback)(
            new Factory(null)
        );

        $result = new ConstRecordType();

        foreach ($mappedValue as $field => $value) {
            if ($value instanceof Spread) {
                $value->applyTypesTo($result);
            } else {
                $result->add(
                    $field,
                    $value->compileTypes()
                );
            }
        }

        return $result;
    }
}
