<?php

namespace Savks\Negotiator\Support\DTO;

use Closure;
use Savks\Negotiator\Exceptions\UnexpectedValue;
use Savks\Negotiator\Support\Types\StringType;
use Stringable;

class StringCast extends NullableCast
{
    protected bool $isStringableAllowed = false;

    public function __construct(
        protected readonly string|Closure|null $accessor = null,
        protected readonly string|Closure|null $default = null
    ) {
    }

    public function allowStringable(): static
    {
        $this->isStringableAllowed = true;

        return $this;
    }

    protected function finalize(mixed $source, array $sourcesTrace): ?string
    {
        $value = static::resolveValueFromAccessor(
            $this->accessor,
            $source,
            $sourcesTrace
        );

        if ($this->accessor && last($sourcesTrace) !== $source) {
            $sourcesTrace[] = $source;
        }

        $value ??= $this->default instanceof Closure ?
            ($this->default)(
                $source,
                ...array_reverse($sourcesTrace)
            ) :
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
}
