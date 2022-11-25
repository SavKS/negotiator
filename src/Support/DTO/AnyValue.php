<?php

namespace Savks\Negotiator\Support\DTO;

use Savks\Negotiator\Exceptions\UnexpectedNull;

abstract class AnyValue
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
            throw new UnexpectedNull(
                static::class,
                \property_exists($this, 'accessor') ?
                    $this->accessor :
                    null
            );
        }

        return $value;
    }
}
