<?php

namespace Savks\Negotiator\Support\DTO;

use Closure;
use Savks\Negotiator\Exceptions\UnexpectedValue;
use Savks\Negotiator\Support\Types\StringType;
use Stringable;

class StringValue extends NullableValue
{
    protected bool $isStringableAllowed = false;

    public function __construct(
        protected mixed $source,
        protected readonly string|Closure|null $accessor = null,
        protected readonly string|Closure|null $default = null
    ) {
    }

    public function allowStringable(): static
    {
        $this->isStringableAllowed = true;

        return $this;
    }

    protected function finalize(): ?string
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

        if (! is_string($value) && $value instanceof Stringable) {
            $value = $value->__toString();
        }

        if (! is_string($value)) {
            throw new UnexpectedValue('string', $value);
        }

        return $value;
    }

    protected function types(): StringType
    {
        return new StringType();
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
    ): ?string {
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

        if (! is_string($value) && $value instanceof Stringable) {
            $value = $value->__toString();
        }

        if (! is_string($value)) {
            throw new UnexpectedValue('string', $value);
        }

        return $value;
    }
}
