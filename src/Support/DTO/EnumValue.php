<?php

namespace Savks\Negotiator\Support\DTO;

use BackedEnum;
use Closure;
use Savks\Negotiator\Contexts\TypeGenerationContext;
use Savks\Negotiator\Exceptions\UnexpectedValue;
use Savks\PhpContexts\Context;

use Savks\Negotiator\Support\Types\{
    AliasType,
    StringType
};

class EnumValue extends NullableValue
{
    /**
     * @param class-string<BackedEnum> $enum
     */
    public function __construct(
        protected readonly mixed $source,
        protected readonly string $enum,
        protected readonly string|Closure|null $accessor = null,
    ) {
    }

    protected function finalize(): string|int|null
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

        if (! is_object($value) && \get_class($value) !== $this->enum) {
            throw new UnexpectedValue("BackedEnum<{$this->enum}>", $value);
        }

        return $value->value;
    }

    protected function types(): StringType|AliasType
    {
        $enumRef = Context::use(TypeGenerationContext::class)->resolveEnumRef(
            $this->enum
        );

        if ($enumRef) {
            return new AliasType($enumRef);
        }

        return new StringType();
    }
}
