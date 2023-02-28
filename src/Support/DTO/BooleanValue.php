<?php

namespace Savks\Negotiator\Support\DTO;

use Closure;
use Savks\Negotiator\Exceptions\UnexpectedValue;
use Savks\Negotiator\Support\Types\BooleanType;

class BooleanValue extends NullableValue
{
    public function __construct(
        protected readonly mixed $source,
        protected readonly string|Closure|null $accessor = null,
        protected readonly bool|null $default = null
    ) {
    }

    protected function finalize(): ?bool
    {
        $value = $this->resolveValueFromAccessor($this->accessor, $this->source);

        if ($this->accessor && last($this->sourcesTrace) !== $this->source) {
            $this->sourcesTrace[] = $this->source;
        }

        $value ??= $this->default;

        if ($value === null) {
            return null;
        }

        if (! \is_bool($value)) {
            throw new UnexpectedValue('boolean', $value);
        }

        return $value;
    }

    protected function types(): BooleanType
    {
        return new BooleanType();
    }
}
