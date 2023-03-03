<?php

namespace Savks\Negotiator\Support\DTO;

use Closure;
use Savks\Negotiator\Exceptions\UnexpectedValue;
use Savks\Negotiator\Support\Types\StringType;

class StringValue extends NullableValue
{
    public function __construct(
        protected readonly mixed $source,
        protected readonly string|Closure|null $accessor = null,
        protected readonly string|Closure|null $default = null
    ) {
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

        if (! \is_string($value)) {
            throw new UnexpectedValue('string', $value);
        }

        return $value;
    }

    protected function types(): StringType
    {
        return new StringType();
    }
}
