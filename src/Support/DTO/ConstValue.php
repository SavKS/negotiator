<?php

namespace Savks\Negotiator\Support\DTO;

/**
 * @template TOriginalValueType
 */
abstract class ConstValue extends Value
{
    /**
     * @return TOriginalValueType
     */
    abstract public function originalValue(): mixed;
}
