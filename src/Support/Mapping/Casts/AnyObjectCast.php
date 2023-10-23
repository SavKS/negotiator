<?php

namespace Savks\Negotiator\Support\Mapping\Casts;

use Closure;
use Savks\Negotiator\Exceptions\UnexpectedValue;

use Savks\Negotiator\Support\TypeGeneration\Types\{
    AliasType,
    RecordType
};

class AnyObjectCast extends NullableCast
{
    use CanBeGeneric;

    public function __construct(
        protected readonly string|Closure|null $accessor = null,
        protected readonly array|object|null $default = null
    ) {
    }

    protected function finalize(mixed $source, array $sourcesTrace): ?object
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

        if (! is_object($value) && ! is_array($value)) {
            throw new UnexpectedValue(['object', 'array<string, mixed>'], $value);
        }

        if (is_array($value) && array_is_list($value)) {
            throw new UnexpectedValue(['array<string, mixed>'], $value);
        }

        return is_array($value) ? (object)$value : $value;
    }

    protected function types(): AliasType|RecordType
    {
        if ($this->assignedToGeneric) {
            return new AliasType($this->assignedToGeneric);
        }

        return new RecordType();
    }
}
