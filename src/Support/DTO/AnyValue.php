<?php

namespace Savks\Negotiator\Support\DTO;

use Closure;
use Savks\Negotiator\Support\Types\AnyType;

class AnyValue extends Value
{
    public bool $nullable = true;

    public function __construct(
        protected readonly mixed $source,
        protected readonly string|Closure|null $accessor = null,
        protected readonly mixed $default = null
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

        return $value;
    }

    protected function types(): AnyType
    {
        return new AnyType();
    }
}
