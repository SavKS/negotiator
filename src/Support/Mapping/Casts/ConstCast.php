<?php

namespace Savks\Negotiator\Support\Mapping\Casts;

use Closure;
use Savks\Negotiator\Exceptions\UnexpectedValue;

/**
 * @template TOriginalValueType
 */
abstract class ConstCast extends Cast
{
    protected string|Closure|null $comparisonAccessor = null;

    /**
     * @return TOriginalValueType
     */
    abstract public function originalValue(): mixed;

    public function check(string|Closure|null $comparisonAccessor): self
    {
        $this->comparisonAccessor = $comparisonAccessor;

        return $this;
    }

    protected function assertMatching(mixed $source, array $sourcesTrace, string|array|null $expectedType = null): void
    {
        if (! $this->comparisonAccessor) {
            return;
        }

        $value = static::resolveValueFromAccessor(
            $this->comparisonAccessor,
            $source,
            $sourcesTrace
        );

        if ($value !== $this->originalValue()) {
            throw new UnexpectedValue(
                $expectedType ?? $this->originalValue(),
                $value
            );
        }
    }
}
