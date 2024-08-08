<?php

namespace Savks\Negotiator\Support\Mapping\Casts;

use Savks\Negotiator\Support\TypeGeneration\Types\{
    BooleanType,
    ConstBooleanType
};

/**
 * @extends ConstCast<bool>
 */
class ConstBooleanCast extends ConstCast
{
    public function __construct(
        protected readonly bool $value,
        protected bool $asAnyBoolean
    ) {
    }

    public function asAnyBoolean(): static
    {
        $this->asAnyBoolean = true;

        return $this;
    }

    public function originalValue(): bool
    {
        return $this->value;
    }

    protected function finalize(mixed $source, array $sourcesTrace): bool
    {
        return $this->value;
    }

    protected function types(): BooleanType|ConstBooleanType
    {
        return $this->asAnyBoolean ? new BooleanType() : new ConstBooleanType($this->value);
    }
}
