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
}
