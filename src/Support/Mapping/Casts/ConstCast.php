<?php

namespace Savks\Negotiator\Support\Mapping\Casts;

/**
 * @template TOriginalValueType
 */
abstract class ConstCast extends Cast
{
    /**
     * @return TOriginalValueType
     */
    abstract public function originalValue(): mixed;
}
