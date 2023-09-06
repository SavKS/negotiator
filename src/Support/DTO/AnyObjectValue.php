<?php

namespace Savks\Negotiator\Support\DTO;

use Closure;

use Savks\Negotiator\Exceptions\{
    JitCompile,
    UnexpectedValue
};
use Savks\Negotiator\Support\Types\{
    AliasType,
    RecordType,
    Type,
    Types
};

class AnyObjectValue extends NullableValue
{
    use CanBeGeneric;

    public function __construct(
        protected readonly mixed $source,
        protected readonly string|Closure|null $accessor = null,
        protected readonly array|object|null $default = null
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

        if (! is_object($value) && ! is_array($value)) {
            throw new UnexpectedValue(['object', 'array<string, mixed>'], $value);
        }

        if (is_array($value) && array_is_list($value)) {
            throw new UnexpectedValue(['array<string, mixed>'], $value);
        }

        return $value;
    }

    protected function types(): Type|Types
    {
        if ($this->assignedToGeneric) {
            return new AliasType($this->assignedToGeneric);
        }

        return new RecordType();
    }

    protected function schema(): array
    {
        return [
            '$$type' => static::class,
            'accessor' => $this->accessor,
            'default' => $this->default,
        ];
    }

    protected static function finalizeUsingSchema(array $schema, mixed $source, array $sourcesTrace = []): mixed
    {
        JitCompile::assertInvalidSchemaType($schema, static::class);

        $value = static::resolveValueFromAccessor(
            $schema['accessor'],
            $source,
            $sourcesTrace
        );

        $value ??= $schema['default'];

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
}
