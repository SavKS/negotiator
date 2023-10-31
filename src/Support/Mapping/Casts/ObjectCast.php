<?php

namespace Savks\Negotiator\Support\Mapping\Casts;

use BackedEnum;
use Closure;
use Illuminate\Support\Stringable;
use Savks\Negotiator\Contexts\TypeGenerationContext;
use stdClass;
use Throwable;

use Savks\Negotiator\Exceptions\{
    InternalException,
    UnexpectedValue
};
use Savks\Negotiator\Support\Mapping\Casts\ObjectUtils\{
    Spread,
    TypedField
};
use Savks\Negotiator\Support\TypeGeneration\Types\{
    AliasType,
    ObjectType,
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
     * @param array<string, Cast>|Spread[]|TypedField[] $schema
     */
    public function __construct(
        protected readonly array $schema,
        protected readonly string|Closure|null $accessor = null
    ) {
    }

    protected function finalize(mixed $source, array $sourcesTrace): ?stdClass
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

        $result = new stdClass();

        foreach ($this->schema as $field => $fieldValue) {
            if ($fieldValue instanceof Spread) {
                $spread = $fieldValue;

                $spread->applyTo($result, $value, $sourcesTrace);
            } elseif ($fieldValue instanceof TypedField) {
                $typedField = $fieldValue;

                try {
                    $fieldAsString = match (true) {
                        $typedField->key instanceof BackedEnum => $typedField->key->value,
                        $typedField->key instanceof Stringable => (string)$typedField->key,

                        default => $typedField->key
                    };

                    $result->{$fieldAsString} = $typedField->value->resolve($value, $sourcesTrace);
                } catch (UnexpectedValue $e) {
                    throw UnexpectedValue::wrap($e, $fieldAsString);
                } catch (Throwable $e) {
                    throw InternalException::wrap($e, $fieldAsString);
                }
            } else {
                if (! $fieldValue instanceof Cast) {
                    throw new UnexpectedValue(Cast::class, $fieldValue);
                }

                try {
                    $result->{$field} = $fieldValue->resolve($value, $sourcesTrace);
                } catch (UnexpectedValue $e) {
                    throw UnexpectedValue::wrap($e, $field);
                } catch (Throwable $e) {
                    throw InternalException::wrap($e, $field);
                }
            }
        }

        return $result;
    }

    protected function types(): ObjectType|Types
    {
        $typeGenerationContext = TypeGenerationContext::useSelf();

        $result = new ObjectType();

        $additionalRecords = [];

        foreach ($this->schema as $field => $value) {
            if ($value instanceof Spread) {
                $spread = $value;

                $spread->applyTypesTo($result);
            } elseif ($value instanceof TypedField) {
                $typedField = $value;

                if ($typedField->key instanceof Stringable) {
                    $fieldAsString = (string)$typedField->key;
                } elseif ($typedField->key instanceof BackedEnum) {
                    $mapperRef = $typeGenerationContext->resolveEnumRef($typedField->key::class);

                    if ($mapperRef) {
                        $additionalRecords[] = new RecordType(
                            new AliasType("{$mapperRef}.{$typedField->key->name}"),
                            $typedField->value->compileTypes()
                        );

                        continue;
                    } else {
                        $fieldAsString = $typedField->key->value;
                    }
                } else {
                    $fieldAsString = $typedField->key;
                }

                $result->add(
                    $fieldAsString,
                    $typedField->value->compileTypes()
                );
            } else {
                $result->add(
                    $field,
                    $value->compileTypes()
                );
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
