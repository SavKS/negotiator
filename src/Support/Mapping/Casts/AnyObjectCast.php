<?php

namespace Savks\Negotiator\Support\Mapping\Casts;

use Closure;
use Savks\Negotiator\Exceptions\UnexpectedValue;
use Savks\Negotiator\Support\TypeGeneration\Types\AliasType;
use Savks\Negotiator\Support\TypeGeneration\Types\AnyType;
use Savks\Negotiator\Support\TypeGeneration\Types\RecordType;
use Savks\Negotiator\Support\TypeGeneration\Types\StringType;
use stdClass;

class AnyObjectCast extends OptionalCast
{
    use CanBeGeneric;

    protected ?Cast $keySchema = null;

    protected ?Cast $valueSchema = null;

    public function __construct(
        protected readonly string|Closure|null $accessor = null,
        protected readonly array|object|null $default = null
    ) {
    }

    public function keySchema(Cast $schema): static
    {
        $this->keySchema = $schema;

        return $this;
    }

    public function valueSchema(Cast $schema): static
    {
        $this->valueSchema = $schema;

        return $this;
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

        if (is_array($value) && ! $value) {
            return new stdClass();
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

        return new RecordType(
            $this->keySchema?->compileTypes() ?? new StringType(),
            $this->valueSchema?->compileTypes() ?? new AnyType()
        );
    }
}
