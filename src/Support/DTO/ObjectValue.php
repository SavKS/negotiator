<?php

namespace Savks\Negotiator\Support\DTO;

use BackedEnum;
use Closure;
use Illuminate\Support\Stringable;
use Savks\Negotiator\Contexts\TypeGenerationContext;
use Savks\Negotiator\Exceptions\UnexpectedValue;
use Savks\Negotiator\Support\DTO\ObjectValue\MissingValue;
use Savks\PhpContexts\Context;

use Savks\Negotiator\Support\DTO\Utils\{
    Factory,
    Record,
    Spread
};
use Savks\Negotiator\Support\Types\{
    AliasType,
    AnyType,
    ConstRecordType,
    RecordType,
    Types
};

class ObjectValue extends NullableValue
{
    /**
     * @var array<string, Value>|null
     */
    protected ?array $value;

    /**
     * @param Closure(Factory): (array|Record) $callback
     */
    public function __construct(
        protected readonly mixed $source,
        protected readonly Closure $callback,
        protected readonly string|Closure|null $accessor = null
    ) {
    }

    protected function finalize(): ?array
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

        /** @var array|Record|mixed $mappedValue */
        $mappedValue = ($this->callback)(
            new Factory($value, $this->sourcesTrace)
        );

        if (! \is_array($mappedValue)
            && (! $mappedValue instanceof Record)
        ) {
            throw new UnexpectedValue([
                'array<string, ' . Value::class . '>',
                Record::class,
            ], $mappedValue);
        }

        $result = [];

        if ($mappedValue instanceof Record) {
            foreach ($mappedValue->entries() as [$field, $fieldValue]) {
                if ($fieldValue instanceof MissingValue) {
                    continue;
                }

                try {
                    $fieldAsString = match (true) {
                        $field instanceof BackedEnum => $field->value,
                        $field instanceof Stringable => (string)$field,

                        default => $field
                    };

                    $result[$fieldAsString] = $fieldValue->compile();
                } catch (UnexpectedValue $e) {
                    throw UnexpectedValue::wrap($e, $fieldAsString);
                }
            }
        } else {
            /** @var Value|Merge|mixed $fieldValue */
            foreach ($mappedValue as $field => $fieldValue) {
                if ($fieldValue instanceof Spread) {
                    $fieldValue->applyTo($result);
                } else {
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
        }

        return $result;
    }

    protected function types(): ConstRecordType|Types
    {
        /** @var array<string, Value|Spread>|Record|mixed $mappedValue */
        $mappedValue = ($this->callback)(
            new Factory(null)
        );

        if (! \is_array($mappedValue)
            && (! $mappedValue instanceof Record)
        ) {
            throw new UnexpectedValue([
                'array<string, ' . Value::class . '>',
                Record::class,
            ], $mappedValue);
        }

        /** @var TypeGenerationContext $typeGenerationContext */
        $typeGenerationContext = Context::use(TypeGenerationContext::class);

        $result = new ConstRecordType();

        $additionalRecords = [];

        if ($mappedValue instanceof Record) {
            foreach ($mappedValue->entries() as [$field, $value]) {
                if ($field instanceof Stringable) {
                    $fieldAsString = (string)$field;
                } elseif ($field instanceof BackedEnum) {
                    $mapperRef = $typeGenerationContext->resolveEnumRef($field::class);

                    if ($mapperRef) {
                        $additionalRecords[] = new RecordType(
                            new AliasType("{$mapperRef}.{$field->name}"),
                            $value->compileTypes()
                        );

                        continue;
                    } else {
                        $fieldAsString = $field->value;
                    }
                } else {
                    $fieldAsString = $field;
                }

                $result->add(
                    $fieldAsString,
                    $value->compileTypes()
                );
            }
        } else {
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
        }

        if (! $additionalRecords) {
            return $result;
        }

        if ($result->props) {
            return new Types([$result, ...$additionalRecords], true);
        }

        return new Types($additionalRecords, true);
    }
}
