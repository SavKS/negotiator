<?php

namespace Savks\Negotiator\Support\DTO;

use Closure;
use Savks\Negotiator\Support\Mapping\Mapper;

trait HasCasts
{
    public function string(string|Closure|null $accessor = null, string $default = null): StringValue
    {
        return new StringValue($this->source, $accessor, $default);
    }

    public function boolean(string|Closure|null $accessor = null, string $default = null): BooleanValue
    {
        return new BooleanValue($this->source, $accessor, $default);
    }

    public function number(string|Closure|null $accessor = null, int|float|null $default = null): NumberValue
    {
        return new NumberValue($this->source, $accessor, $default);
    }

    public function date(string|Closure|null $accessor = null, string|Closure|null $format = null): DateValue
    {
        return new DateValue($this->source, $accessor, $format);
    }

    public function anyObject(string|Closure|null $accessor = null, string $default = null): AnyObjectValue
    {
        return new AnyObjectValue($this->source, $accessor, $default);
    }

    public function array(string|Closure $iterator, string|Closure|null $accessor = null): ArrayValue
    {
        return new ArrayValue($this->source, $iterator, $accessor);
    }

    public function object(Closure $callback, string|Closure|null $accessor = null): ObjectValue
    {
        return new ObjectValue($this->source, $callback, $accessor);
    }

    public function mapper(Mapper|Closure $mapper, string|Closure|null $accessor = null): MapperValue
    {
        return new MapperValue($this->source, $mapper, $accessor);
    }
}
