<?php

namespace Savks\Negotiator\Support\DTO;

use Closure;

use Savks\Negotiator\Support\Types\{
    AliasType,
    AnyType
};

class AnyValue extends NullableValue
{
    use CanBeGeneric;

    public bool $nullable = true;

    public function __construct(
        protected readonly mixed $source,
        protected readonly string|Closure|null $accessor = null,
        protected readonly mixed $default = null
    ) {
    }

    protected function finalize(): object|array|null
    {
        $value = $this->resolveValueFromAccessor(
            $this->accessor,
            $this->source,
            $this->sourcesTrace
        );

        if ($this->accessor && last($this->sourcesTrace) !== $this->source) {
            $this->sourcesTrace[] = $this->source;
        }

        $value ??= $this->default;

        if ($value === null) {
            return null;
        }

        return $value;
    }

    protected function types(): AnyType|AliasType
    {
        if ($this->assignedToGeneric) {
            return new AliasType($this->assignedToGeneric);
        }

        return new AnyType();
    }
}
