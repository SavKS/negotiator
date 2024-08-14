<?php

namespace Savks\Negotiator\Support\Mapping\Casts;

use Savks\Negotiator\Support\TypeGeneration\Types\{
    ConstNumberType,
    NumberType
};

class ConstNumberCast extends ConstCast
{
    protected bool $asAnyNumber = false;

    public function __construct(
        protected readonly int|float $value,
        bool $asAnyNumber
    ) {
        $this->asAnyNumber = $asAnyNumber;
    }

    public function asAnyNumber(): static
    {
        $this->asAnyNumber = true;

        return $this;
    }

    public function originalValue(): int|float
    {
        return $this->value;
    }

    protected function finalize(mixed $source, array $sourcesTrace): int|float
    {
        $this->assertMatching($source, $sourcesTrace);

        return $this->value;
    }

    protected function types(): NumberType|ConstNumberType
    {
        return $this->asAnyNumber ? new NumberType() : new ConstNumberType($this->value);
    }
}
