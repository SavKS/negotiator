<?php

namespace Savks\Negotiator\Support\DTO\ArrayValue;

use Savks\Negotiator\Support\DTO\Castable;

class Item extends Castable
{
    public function __construct(
        public readonly int $index,
        public readonly int|string $key,
        mixed $source,
        array $sourcesTrace = [],
    ) {
        parent::__construct($source, $sourcesTrace);
    }
}
