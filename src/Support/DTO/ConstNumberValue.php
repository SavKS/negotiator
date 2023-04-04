<?php

namespace Savks\Negotiator\Support\DTO;

use Savks\Negotiator\Support\Types\{
    ConstNumberType,
    NumberType
};

class ConstNumberValue extends ConstValue
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

    protected function finalize(): int|float
    {
        return $this->value;
    }

    protected function types(): NumberType|ConstNumberType
    {
        return $this->asAnyNumber ? new NumberType() : new ConstNumberType($this->value);
    }
}
