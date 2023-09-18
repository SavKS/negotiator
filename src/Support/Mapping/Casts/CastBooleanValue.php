<?php

namespace Savks\Negotiator\Support\Mapping\Casts;

use Savks\Negotiator\Support\TypeGeneration\Types\{
    BooleanType,
    ConstBooleanType
};

/**
 * @extends ConstCast<bool>
 */
class CastBooleanValue extends ConstCast
{
    public function __construct(
        protected readonly bool $value,
        protected readonly bool $asAnyBool
    ) {
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
        return $this->asAnyBool ? new BooleanType() : new ConstBooleanType($this->value);
    }
}
