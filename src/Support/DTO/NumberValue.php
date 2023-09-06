<?php

namespace Savks\Negotiator\Support\DTO;

use Closure;
use Savks\Negotiator\Support\Types\NumberType;

use Savks\Negotiator\Exceptions\{
    JitCompile,
    UnexpectedValue
};

class NumberValue extends NullableValue
{
    public function __construct(
        protected readonly mixed $source,
        protected readonly string|Closure|null $accessor = null,
        protected readonly int|float|Closure|null $default = null
    ) {
    }

    protected function finalize(): int|float|null
    {
        $value = $this->resolveValueFromAccessor(
            $this->accessor,
            $this->source,
            $this->sourcesTrace
        );

        if ($this->accessor && last($this->sourcesTrace) !== $this->source) {
            $this->sourcesTrace[] = $this->source;
        }

        $value ??= $this->default instanceof Closure ?
            ($this->default)($this->source, ...$this->sourcesTrace) :
            $this->default;

        if ($value === null) {
            return null;
        }

        if (! is_numeric($value)) {
            throw new UnexpectedValue('int', $value);
        }

        return $value;
    }

    protected function types(): NumberType
    {
        return new NumberType();
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
    ): int|float|null {
        JitCompile::assertInvalidSchemaType($schema, static::class);

        $value = static::resolveValueFromAccessor(
            $schema['accessor'],
            $source,
            $sourcesTrace
        );

        if ($schema['accessor'] && last($sourcesTrace) !== $source) {
            $sourcesTrace[] = $source;
        }

        $value ??= $schema['default'] instanceof Closure ?
            ($schema['default'])($source, ...$sourcesTrace) :
            $schema['default'];

        if ($value === null) {
            return null;
        }

        if (! is_numeric($value)) {
            throw new UnexpectedValue('int', $value);
        }

        return $value;
    }
}
