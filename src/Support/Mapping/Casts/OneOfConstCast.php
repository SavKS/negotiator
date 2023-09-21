<?php

namespace Savks\Negotiator\Support\Mapping\Casts;

use BackedEnum;
use Closure;
use RuntimeException;
use Savks\Negotiator\Exceptions\UnexpectedValue;

use Savks\Negotiator\Support\TypeGeneration\Types\{
    BooleanType,
    ConstBooleanType,
    ConstNumberType,
    ConstStringType,
    NumberType,
    StringType,
    Types
};

class OneOfConstCast extends NullableCast
{
    /**
     * @param ConstCast[] $values
     */
    public function __construct(
        protected readonly array $values,
        protected readonly string|Closure|null $accessor = null
    ) {
    }

    protected function finalize(mixed $source, array $sourcesTrace): string|int|float|bool|null
    {
        $value = static::resolveValueFromAccessor(
            $this->accessor,
            $source,
            $sourcesTrace
        );

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
                if ($constValue instanceof ConstEnumCast) {
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
