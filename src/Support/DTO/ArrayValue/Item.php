<?php

namespace Savks\Negotiator\Support\DTO\ArrayValue;

use Savks\Negotiator\Support\DTO\HasCasts;

class Item
{
    use HasCasts;

    public function __construct(protected readonly mixed $source)
    {
    }
}
