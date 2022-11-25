<?php

namespace Savks\Negotiator\Support\DTO;

use Closure;
use Savks\Negotiator\Exceptions\UnexpectedFinalValue;

class AnyObjectValue extends AnyValue
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

        if (! \is_object($value)
            || \array_is_list($value)
        ) {
            throw new UnexpectedFinalValue(
                static::class,
                ['object', 'array<string, mixed>'],
                $value,
                $this->accessor
            );
        }

        return $value;
    }
}
