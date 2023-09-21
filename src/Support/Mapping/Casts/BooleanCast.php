<?php

namespace Savks\Negotiator\Support\Mapping\Casts;

use Closure;
use Savks\Negotiator\Exceptions\UnexpectedValue;
use Savks\Negotiator\Support\TypeGeneration\Types\BooleanType;

class BooleanCast extends NullableCast
{
    public function __construct(
        protected readonly string|Closure|null $accessor = null,
        protected readonly bool|null $default = null
    ) {
    }

    protected function finalize(mixed $source, array $sourcesTrace): ?bool
    {
        $value = static::resolveValueFromAccessor(
            $this->accessor,
            $source,
            $sourcesTrace
        );

        $value ??= $this->default;

        if ($value === null) {
            return null;
        }

        if (! is_bool($value)) {
            throw new UnexpectedValue('boolean', $value);
        }

        return $value;
    }

    protected function types(): BooleanType
    {
        return new BooleanType();
    }
}