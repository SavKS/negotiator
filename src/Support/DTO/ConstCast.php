<?php

namespace Savks\Negotiator\Support\DTO;

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
