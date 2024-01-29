<?php

namespace Savks\Negotiator\Support\Mapping\Casts;

use BackedEnum;
use Closure;
use Savks\Negotiator\Contexts\TypeGenerationContext;
use Savks\Negotiator\Enums\RefTypes;
use Savks\Negotiator\Exceptions\UnexpectedValue;

use Savks\Negotiator\Support\TypeGeneration\Types\{
    AliasType,
    StringType
};

class EnumCast extends OptionalCast
{
    protected bool $tryCast = false;

    /**
     * @param class-string<BackedEnum> $enum
     */
    public function __construct(
        protected readonly string $enum,
        protected readonly string|Closure|null $accessor = null,
    ) {
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
            return null;
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

    protected function types(): StringType|AliasType
    {
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
