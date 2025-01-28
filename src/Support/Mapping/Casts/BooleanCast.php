<?php

namespace Savks\Negotiator\Support\Mapping\Casts;

use Closure;
use Savks\Negotiator\Enums\OptionalModes;
use Savks\Negotiator\Exceptions\UnexpectedValue;
use Savks\Negotiator\Support\TypeGeneration\Types\BooleanType;

class BooleanCast extends OptionalCast
{
    protected bool $allowCast = false;

    protected bool $falseIfNull = false;

    public function __construct(
        protected readonly string|Closure|null $accessor = null,
        protected readonly ?bool $default = null
    ) {
    }

    public function falseIfNull(): static
    {
        $this->falseIfNull = true;

        return $this;
    }

    public function allowCast(): static
    {
        $this->allowCast = true;

        return $this;
    }

    public function forceCast(): static
    {
        return $this->falseIfNull()->allowCast();
    }

    public function optionalIfFalse(): static
    {
        return $this->optional(OptionalModes::FALSE_AS_OPTIONAL);
    }

    protected function finalize(mixed $source, array $sourcesTrace): ?bool
    {
        $value = static::resolveValueFromAccessor(
            $this->accessor,
            $source,
            $sourcesTrace
        );

        $value ??= $this->default;

        if ($value === null) {
            if ($this->falseIfNull) {
                return false;
            }

            return null;
        }

        if (! is_bool($value)) {
            if ($this->allowCast) {
                return (bool)$value;
            }

            throw new UnexpectedValue('boolean', $value);
        }

        return $value;
    }

    protected function types(): BooleanType
    {
        return new BooleanType();
    }
}
