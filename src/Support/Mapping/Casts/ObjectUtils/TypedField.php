<?php

namespace Savks\Negotiator\Support\Mapping\Casts\ObjectUtils;

use BackedEnum;
use Savks\Negotiator\Support\Mapping\Casts\Cast;
use Stringable;

class TypedField
{
    public function __construct(
        public readonly string|int|Stringable|BackedEnum $key,
        public readonly Cast $value
    ) {
    }
}
