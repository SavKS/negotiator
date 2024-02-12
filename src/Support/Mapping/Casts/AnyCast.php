<?php

namespace Savks\Negotiator\Support\Mapping\Casts;

use Closure;

use Savks\Negotiator\Support\TypeGeneration\Types\{
    AliasType,
    AnyType
};

class AnyCast extends OptionalCast
{
    use CanBeGeneric;

    public bool $nullable = true;

    public function __construct(
        protected readonly string|Closure|null $accessor = null,
        protected readonly mixed $default = null
    ) {
    }

    protected function finalize(mixed $source, array $sourcesTrace): mixed
    {
        $value = static::resolveValueFromAccessor(
            $this->accessor,
            $source,
            $sourcesTrace
        );

        $value ??= $this->default;

        if ($value === null) {
            return null;
        }

        return $value;
    }

    protected function types(): AliasType|AnyType
    {
        if ($this->assignedToGeneric) {
            return new AliasType($this->assignedToGeneric);
        }

        return new AnyType();
    }
}
