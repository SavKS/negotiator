<?php

namespace Savks\Negotiator\Support\Mapping\Casts;

use Savks\Negotiator\Support\TypeGeneration\Types\{
    ConstNumberType,
    NumberType
};

class ConstNumberCast extends ConstCast
{
    public function __construct(
        protected readonly int|float $value,
        protected readonly bool $asAnyNumber
    ) {
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
