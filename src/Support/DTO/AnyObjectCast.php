<?php

namespace Savks\Negotiator\Support\DTO;

use Closure;
use Savks\Negotiator\Exceptions\UnexpectedValue;

use Savks\Negotiator\Support\Types\{
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

    protected function finalize(mixed $source, array $sourcesTrace): object|array|null
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

        return $value;
    }

    protected function types(): AliasType|RecordType
    {
        if ($this->assignedToGeneric) {
            return new AliasType($this->assignedToGeneric);
        }

        return new RecordType();
    }
}
