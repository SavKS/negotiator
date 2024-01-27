<?php

namespace Savks\Negotiator\Support\Mapping\Casts;

use Closure;
use Savks\Negotiator\Exceptions\UnexpectedValue;
use Savks\Negotiator\Support\TypeGeneration\Types\NumberType;

class NumberCast extends OptionalCast
{
    public function __construct(
        protected readonly string|Closure|null $accessor = null,
        protected readonly int|float|Closure|null $default = null
    ) {
    }

    protected function finalize(mixed $source, array $sourcesTrace): int|float|null
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
