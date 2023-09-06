<?php

namespace Savks\Negotiator\Support\DTO;

use BackedEnum;
use Closure;
use RuntimeException;
use Savks\Negotiator\Exceptions\UnexpectedValue;

use Savks\Negotiator\Support\Types\{
    BooleanType,
    ConstBooleanType,
    ConstNumberType,
    ConstStringType,
    NumberType,
    StringType,
    Types
};

class OneOfConstValue extends NullableValue
{
    /**
     * @param ConstValue[] $values
     */
    public function __construct(
        protected readonly mixed $source,
        protected readonly array $values,
        protected readonly string|Closure|null $accessor = null
    ) {
    }

    protected function finalize(): string|int|float|bool|null
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

        $isMatched = null;

        foreach ($this->values as $constValue) {
            $isMatched = $constValue->originalValue() === $value;

            if ($isMatched) {
                break;
            }
        }

        if (! $isMatched) {
            $types = [];

            foreach ($this->values as $constValue) {
                if ($constValue instanceof ConstEnumValue) {
                    $types[] = 'BackedEnum<' . $constValue->originalValue()::class . '>';
                } else {
                    foreach ($constValue->compileTypes()->types as $type) {
                        $types[] = match (true) {
                            $type instanceof ConstBooleanType => $type->value ? 'true' : 'false',

                            $type instanceof ConstNumberType, $type instanceof ConstStringType => $type->value,

                            $type instanceof BooleanType => 'bool',
                            $type instanceof NumberType => 'numeric',
                            $type instanceof StringType => 'string',

                            default => throw new RuntimeException(
                                sprintf('Unprocessed type "%s".', $type::class)
                            )
                        };
                    }
                }
            }

            throw new UnexpectedValue($types, $value);
        }

        return $value instanceof BackedEnum ? $value->value : $value;
    }

    protected function types(): Types
    {
        $types = [];

        foreach ($this->values as $value) {
            $types[] = $value->compileTypes()->types;
        }

        return new Types(
            array_merge(...$types)
        );
    }

    protected function schema(): array
    {
        $result = [
            '$$type' => static::class,
            'accessor' => $this->accessor,
            'values' => [],
        ];

        foreach ($this->values as $constValue) {
            $result['values'][] = [
                'schema' => $constValue->compileSchema(),
                'originalValue' => $constValue->originalValue(),
            ];
        }

        return $result;
    }

    protected static function finalizeUsingSchema(array $schema, mixed $source, array $sourcesTrace = []): mixed
    {
        $value = static::resolveValueFromAccessor(
            $schema['accessor'],
            $source,
            $sourcesTrace
        );

        if ($value === null) {
            return null;
        }

        $isMatched = null;

        foreach ($schema['values'] as $constValue) {
            $isMatched = $constValue['originalValue'] === $value;

            if ($isMatched) {
                break;
            }
        }

        if (! $isMatched) {
            $types = [];

            foreach ($schema['values'] as $constValue) {
                if ($constValue['$$type'] === ConstEnumValue::class) {
                    $types[] = 'BackedEnum<' . $constValue->originalValue()::class . '>';
                } else {
                    $types[] = match ($constValue['$$type']) {
                        ConstBooleanType::class => $constValue['originalValue'] ? 'true' : 'false',

                        ConstNumberType::class, ConstStringType::class => $constValue['originalValue'],

                        BooleanType::class => 'bool',
                        NumberType::class => 'numeric',
                        StringType::class => 'string',

                        default => throw new RuntimeException(
                            "Unprocessed type \"{$constValue['$$type']}\"."
                        )
                    };
                }
            }

            throw new UnexpectedValue($types, $value);
        }

        return $value instanceof BackedEnum ? $value->value : $value;
    }
}
