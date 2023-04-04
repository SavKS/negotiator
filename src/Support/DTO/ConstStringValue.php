<?php

namespace Savks\Negotiator\Support\DTO;

use Savks\Negotiator\Support\Types\{
    ConstStringType,
    StringType
};

class ConstStringValue extends ConstValue
{
    public function __construct(
        protected readonly string $value,
        protected readonly bool $asAnyString
    ) {
    }

    public function originalValue(): string
    {
        return $this->value;
    }

    protected function finalize(): string
    {
        return $this->value;
    }

    protected function types(): StringType|ConstStringType
    {
        return $this->asAnyString ? new StringType() : new ConstStringType($this->value);
    }
}
