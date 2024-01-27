<?php

namespace Savks\Negotiator\Support\Mapping\Casts;

use Closure;
use Savks\Negotiator\Exceptions\UnexpectedValue;
use Savks\Negotiator\Support\TypeGeneration\Types\StringType;
use Stringable;

class StringCast extends OptionalCast
{
    protected bool $isStringableAllowed = false;

    protected bool $isCastNumericAllowed = false;

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

    public function allowCastNumeric(): static
    {
        $this->isCastNumericAllowed = true;

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

        if (! is_string($value)) {
            if ($this->isStringableAllowed) {
                if ($value instanceof Stringable) {
                    $value = $value->__toString();
                } else {
                    throw new UnexpectedValue('string', $value);
                }
            }

            if (is_float($value) || is_int($value)) {
                if ($this->isCastNumericAllowed) {
                    $value = (string)$value;
                } else {
                    throw new UnexpectedValue('string', $value);
                }
            }
        }

        return $value;
    }

    protected function types(): StringType
    {
        return new StringType();
    }
}
