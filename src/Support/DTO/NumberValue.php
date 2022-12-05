<?php

namespace Savks\Negotiator\Support\DTO;

use Closure;

use Savks\Negotiator\Support\Types\{
    NumberType,
    Type
};

class NumberValue extends Value
{
    public function __construct(
        protected readonly mixed $source,
        protected readonly string|Closure|null $accessor = null,
        protected readonly int|float|null $default = null
    ) {
    }

    protected function finalize(): int|float|null
    {
        if ($this->accessor === null) {
            $value = $this->source;
        } elseif (\is_string($this->accessor)) {
            $value = \data_get($this->source, $this->accessor);
        } else {
            $value = ($this->accessor)($this->source);
        }

        $value ??= $this->default;

        if ($value === null) {
            return null;
        }

        if (! \is_numeric($value)) {
            throw new UnexpectedValue('int', $value);
        }

        return $value;
    }

    protected function types(): NumberType
    {
        return new NumberType();
    }
}
