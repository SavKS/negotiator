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
        protected readonly string|null $default = null
    ) {
    }

    protected function finalize(): ?string
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
