<?php

namespace Savks\Negotiator\Support\DTO;

use ReflectionClass;
use Savks\Negotiator\Exceptions\UnexpectedNull;

abstract class Value
{
    public bool $nullable = false;

    public function nullable(): static
    {
        $this->nullable = true;

        return $this;
    }

    abstract protected function finalize(): mixed;

    public function compile(): mixed
    {
        $value = $this->finalize();

        if ($value === null && ! $this->nullable) {
            throw new UnexpectedNull('NOT NULL', $value);
        }

        return $value;
    }
}
