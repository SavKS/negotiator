<?php

namespace Savks\Negotiator\Support\DTO;

use BackedEnum;
use Closure;
use Illuminate\Support\Stringable;
use Savks\Negotiator\Contexts\TypeGenerationContext;
use Savks\Negotiator\Exceptions\UnexpectedValue;
use Savks\PhpContexts\Context;

use Savks\Negotiator\Support\DTO\Utils\{
    Record,
    Spread
};
use Savks\Negotiator\Support\Types\{
    AliasType,
    ConstRecordType,
    RecordType,
    Types
};

class ObjectCast extends NullableCast
{
    /**
     * @var array<string, Cast>|null
     */
    protected ?array $value;

    /**
     * @param array<string, Cast>|Record $schema
     */
    public function __construct(
        protected readonly array $schema,
        protected readonly string|Closure|null $accessor = null
    ) {
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

        $result = [];

        if ($this->schema instanceof Record) {
            foreach ($this->schema->entries() as [$field, $fieldValue]) {
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
            foreach ($this->schema as $field => $fieldValue) {
                if ($fieldValue instanceof Spread) {
                    $fieldValue->applyTo($value, $sourcesTrace, $result);
                } else {
                    if (! $fieldValue instanceof Cast) {
                        throw new UnexpectedValue(Cast::class, $fieldValue);
                    }

                    try {
                        $result[$field] = $fieldValue->resolve($value, $sourcesTrace);
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
        $typeGenerationContext = Context::use(TypeGenerationContext::class);

        $result = new ConstRecordType();

        $additionalRecords = [];

        if ($this->schema instanceof Record) {
            foreach ($this->schema->entries() as [$field, $value]) {
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
            foreach ($this->schema as $field => $value) {
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
