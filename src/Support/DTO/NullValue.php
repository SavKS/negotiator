<?php

namespace Savks\Negotiator\Support\DTO;

use Savks\Negotiator\Support\Types\NullType;

class NullValue extends NullableValue
{
    public bool $nullable = true;

    public function __construct()
    {
    }

    protected function finalize(): mixed
    {
        return null;
    }

    protected function types(): NullType
    {
        return new NullType();
    }
}
