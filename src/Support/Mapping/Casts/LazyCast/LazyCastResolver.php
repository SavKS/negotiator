<?php

namespace Savks\Negotiator\Support\Mapping\Casts\LazyCast;

use JsonSerializable;
use LogicException;
use Savks\Negotiator\Support\Mapping\Casts\Cast;

final readonly class LazyCastResolver implements JsonSerializable
{
    public function __construct(
        protected mixed $lazyValue,
        protected Cast $schema
    ) {
    }

    public function jsonSerialize(): mixed
    {
        if (
            $this->lazyValue !== null
            && ! method_exists($this->lazyValue, 'resolve')
        ) {
            throw new LogicException('Lazy value object must have "resolve" method.');
        }

        return $this->schema->resolve(
            $this->lazyValue?->resolve()
        );
    }
}
