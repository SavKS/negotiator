<?php

namespace Savks\Negotiator\Support\Mapping\Casts;

use BackedEnum;
use Closure;
use Illuminate\Support\Stringable;
use Savks\Negotiator\Contexts\TypeGenerationContext;
use Savks\Negotiator\Enums\RefTypes;
use Savks\Negotiator\Exceptions\InternalException;
use Savks\Negotiator\Exceptions\UnexpectedValue;
use Savks\Negotiator\Support\Mapping\Casts\ObjectUtils\Spread;
use Savks\Negotiator\Support\Mapping\Casts\ObjectUtils\TypedField;
use Savks\Negotiator\Support\TypeGeneration\Types\AliasType;
use Savks\Negotiator\Support\TypeGeneration\Types\AnyType;
use Savks\Negotiator\Support\TypeGeneration\Types\ObjectType;
use Savks\Negotiator\Support\TypeGeneration\Types\RecordType;
use Savks\Negotiator\Support\TypeGeneration\Types\StringType;
use Savks\Negotiator\Support\TypeGeneration\Types\Types;
use stdClass;
use Throwable;

class ObjectCast extends OptionalCast
{
    use WorkWithOptionalFields;

    /**
     * @var array<string, Cast>|null
     */
    protected ?array $value;

    /**
     * @var array{
     *     keySchema: Cast|null,
     *     valueSchema: Cast|null,
     * }|null
     */
    protected ?array $asAnyObject = null;

    protected bool $associative = false;

    /**
     * @param array<string, Cast>|Spread[]|TypedField[] $schema
     */
    public function __construct(
        protected readonly array $schema,
        protected readonly string|Closure|null $accessor = null
    ) {
    }

    public function associative(): static
    {
        $this->associative = true;

        return $this;
    }

    public function asAnyObject(?Cast $keySchema = null, ?Cast $valueSchema = null): static
    {
        $this->asAnyObject = [
            'keySchema' => $keySchema,
            'valueSchema' => $valueSchema,
        ];

        return $this;
    }

    /**
     * @param mixed[] $sourcesTrace
     *
     * @return stdClass|array<array-key, mixed>|null
     *
     * @throws Throwable
     */
    protected function finalize(mixed $source, array $sourcesTrace): stdClass|array|null
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

        $hasValues = false;

        foreach ($this->schema as $field => $fieldValue) {
            if ($fieldValue instanceof Spread) {
                $spread = $fieldValue;

                $spread->applyTo($result, $value, $sourcesTrace);
            } elseif ($fieldValue instanceof TypedField) {
                $typedField = $fieldValue;

                $fieldAsString = match (true) {
                    $typedField->key instanceof BackedEnum => $typedField->key->value,
                    $typedField->key instanceof Stringable => (string)$typedField->key,

                    default => $typedField->key
                };

                try {
                    $resolvedValue = $typedField->value->resolve($value, $sourcesTrace);

                    if (! $this->needSkip($resolvedValue, $typedField->value)) {
                        $result->{$fieldAsString} = $resolvedValue;

                        $hasValues = true;
                    }
                } catch (UnexpectedValue $e) {
                    throw UnexpectedValue::wrap($e, $fieldAsString);
                } catch (Throwable $e) {
                    throw InternalException::wrap($e, $fieldAsString);
                }
            } else {
                try {
                    $resolvedValue = $fieldValue->resolve($value, $sourcesTrace);

                    if (! $this->needSkip($resolvedValue, $fieldValue)) {
                        $result->{$field} = $resolvedValue;

                        $hasValues = true;
                    }
                } catch (UnexpectedValue $e) {
                    throw UnexpectedValue::wrap($e, $field);
                } catch (Throwable $e) {
                    throw InternalException::wrap($e, $field);
                }
            }
        }

        if (! $hasValues && $this->associative) {
            return null;
        }

        return $this->associative ? (array)$result : $result;
    }

    protected function types(): ObjectType|RecordType|Types
    {
        if ($this->asAnyObject) {
            $keyType = $this->asAnyObject['keySchema']?->compileTypes() ?? new StringType();
            $valueType = $this->asAnyObject['valueSchema']?->compileTypes() ?? new AnyType();

            return new RecordType($keyType, $valueType);
        }

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
                    $enumRef = $typeGenerationContext->resolveEnumRef($typedField->key::class);

                    if ($enumRef) {
                        $additionalRecords[] = new RecordType(
                            new AliasType("{$enumRef}.{$typedField->key->name}", ref: [
                                'type' => RefTypes::ENUM,
                                'fqn' => $typedField->key::class,
                            ]),
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
