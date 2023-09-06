<?php

namespace Savks\Negotiator\Support\DTO;

use Closure;
use Savks\Negotiator\Exceptions\JitCompile;

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

    protected function schema(): array
    {
        return [
            '$$type' => static::class,
            'accessor' => $this->accessor,
            'default' => $this->default,
        ];
    }

    protected static function finalizeUsingSchema(
        array $schema,
        mixed $source,
        array $sourcesTrace = []
    ): object|array|null {
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

        return $value;
    }
}
