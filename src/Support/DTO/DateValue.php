<?php

namespace Savks\Negotiator\Support\DTO;

use Closure;
use DateTime;
use Savks\Negotiator\Exceptions\UnexpectedFinalValue;

use Carbon\{
        Carbon,
        CarbonInterface
};

class DateValue extends AnyValue
{
    public function __construct(
        protected readonly mixed $source,
        protected readonly string|Closure|null $accessor = null,
        protected readonly string|Closure|null $format = null
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

        if ($value === null) {
            return null;
        }

        if ($value instanceof CarbonInterface) {
            $carbon = $value;
        } elseif ($value instanceof DateTime) {
            $carbon = new Carbon($value);
        } else {
            throw new UnexpectedFinalValue(static::class, [
                CarbonInterface::class,
                DateTime::class,
            ], $value, $this->accessor);
        }

        if ($this->format) {
            $format = \is_string($this->format) ? $carbon->format($this->format) : ($this->format)($carbon);
        } else {
            $format = 'Y-m-d H:i:s';
        }

        return $carbon->format($format);
    }
}
