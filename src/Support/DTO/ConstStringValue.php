<?php

namespace Savks\Negotiator\Support\DTO;

use Savks\Negotiator\Support\Types\{
    ConstStringType,
    StringType
};

class ConstStringValue extends Value
{
    public function __construct(
        protected readonly string $value,
        protected readonly bool $asAnyString
    ) {
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
