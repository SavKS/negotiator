<?php

namespace Savks\Negotiator\Support\Mapping\Casts;

use BackedEnum;
use Closure;
use ReflectionEnum;
use ReflectionException;
use Savks\Negotiator\Contexts\TypeGenerationContext;
use Savks\Negotiator\Enums\RefTypes;
use Savks\Negotiator\Exceptions\UnexpectedValue;
use Savks\Negotiator\Support\TypeGeneration\Types\AliasType;
use Savks\Negotiator\Support\TypeGeneration\Types\ConstNumberType;
use Savks\Negotiator\Support\TypeGeneration\Types\ConstStringType;
use Savks\Negotiator\Support\TypeGeneration\Types\StringType;
use Savks\Negotiator\Support\TypeGeneration\Types\Types;

class EnumCast extends OptionalCast
{
    protected bool $tryCast = false;

    protected bool $unpack = false;

    /**
     * @param class-string<BackedEnum> $enum
     */
    public function __construct(
        protected readonly string $enum,
        protected readonly string|Closure|null $accessor = null,
        protected readonly ?BackedEnum $defaultValue = null
    ) {
    }

    public function unpack(): static
    {
        $this->unpack = true;

        return $this;
    }

    public function tryCast(): static
    {
        $this->tryCast = true;

        return $this;
    }

    protected function finalize(mixed $source, array $sourcesTrace): string|int|null
    {
        $value = static::resolveValueFromAccessor(
            $this->accessor,
            $source,
            $sourcesTrace
        );

        if ($value === null) {
            return $this->defaultValue?->value;
        }

        /** @var class-string<BackedEnum> $enum */
        $enum = $this->enum;

        if (! is_object($value) || get_class($value) !== $this->enum) {
            if (
                $this->tryCast
                && (is_string($value) || is_numeric($value))
                && $enum::tryFrom($value)
            ) {
                return $value;
            }

            throw new UnexpectedValue("BackedEnum<{$this->enum}>", $value);
        }

        return $value->value;
    }

    /**
     * @throws ReflectionException
     */
    protected function types(): StringType|AliasType|Types
    {
        if ($this->unpack) {
            $refEnum = new ReflectionEnum($this->enum);

            $enumType = $refEnum->getBackingType()->getName();

            $types = [];

            foreach ($this->enum::cases() as $case) {
                if ($enumType === 'string') {
                    $types[] = new ConstStringType($case->value);
                } else {
                    $types[] = new ConstNumberType($case->value);
                }
            }

            return new Types($types);
        }

        $enumRef = TypeGenerationContext::useSelf()->resolveEnumRef($this->enum);

        if ($enumRef) {
            return new AliasType($enumRef, ref: [
                'type' => RefTypes::ENUM,
                'fqn' => $this->enum,
            ]);
        }

        return new StringType();
    }
}
