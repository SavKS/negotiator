<?php

namespace Savks\Negotiator\Support\DTO;

use BackedEnum;
use Closure;
use Savks\Negotiator\Contexts\TypeGenerationContext;
use Savks\Negotiator\Exceptions\UnexpectedValue;

use Savks\Negotiator\Support\Types\{
    AliasType,
    StringType
};

class EnumCast extends NullableCast
{
    /**
     * @param class-string<BackedEnum> $enum
     */
    public function __construct(
        protected readonly string $enum,
        protected readonly string|Closure|null $accessor = null,
    ) {
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

        if (! is_object($value) && get_class($value) !== $this->enum) {
            throw new UnexpectedValue("BackedEnum<{$this->enum}>", $value);
        }

        return $value->value;
    }

    protected function types(): StringType|AliasType
    {
        $enumRef = TypeGenerationContext::useSelf()->resolveEnumRef($this->enum);

        if ($enumRef) {
            return new AliasType($enumRef);
        }

        return new StringType();
    }
}
