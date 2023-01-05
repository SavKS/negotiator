<?php

namespace Savks\Negotiator\Support\DTO;

use Closure;
use Savks\Negotiator\Exceptions\UnexpectedValue;

use Savks\Negotiator\Support\Types\{
    RecordType,
    Type,
    Types
};

class AnyObjectValue extends NullableValue
{
    public function __construct(
        protected readonly mixed $source,
        protected readonly string|Closure|null $accessor = null,
        protected readonly array|object|null $default = null
    ) {
    }

    protected function finalize(): object|array|null
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

        if (! \is_object($value) && ! \is_array($value)) {
            throw new UnexpectedValue(['object', 'array<string, mixed>'], $value);
        }

        if (\is_array($value) && \array_is_list($value)) {
            throw new UnexpectedValue(['array<string, mixed>'], $value);
        }

        return $value;
    }

    protected function types(): Type|Types
    {
        return new RecordType();
    }
}
