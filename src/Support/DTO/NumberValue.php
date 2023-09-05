<?php

namespace Savks\Negotiator\Support\DTO;

use Closure;
use Savks\Negotiator\Exceptions\UnexpectedValue;
use Savks\Negotiator\Support\Types\NumberType;

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
}
