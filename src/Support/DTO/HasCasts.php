<?php

namespace Savks\Negotiator\Support\DTO;

use Carbon\CarbonInterface;
use Closure;
use DateTime;
use Savks\Negotiator\Support\Mapping\Mapper;

trait HasCasts
{
    public function string(string|Closure|null $accessor = null, string $default = null): StringValue
    {
        return new StringValue($this->source, $accessor, $default);
    }

    public function constString(string|null $value): StringValue
    {
        return new StringValue(null, fn () => $value);
    }

    public function boolean(string|Closure|null $accessor = null, string $default = null): BooleanValue
    {
        return new BooleanValue($this->source, $accessor, $default);
    }

    public function constBoolean(bool|null $value): BooleanValue
    {
        return new BooleanValue(null, fn () => $value);
    }

    public function number(string|Closure|null $accessor = null, int|float|null $default = null): NumberValue
    {
        return new NumberValue($this->source, $accessor, $default);
    }

    public function constNumber(int|float|null $value): NumberValue
    {
        return new NumberValue(null, fn () => $value);
    }

    public function date(string|Closure|null $accessor = null, string|Closure|null $format = null): DateValue
    {
        return new DateValue($this->source, $accessor, $format);
    }

    public function constDate(CarbonInterface|DateTime|null $value, string|Closure|null $format = null): DateValue
    {
        return new DateValue(null, fn () => $value, $format);
    }

    public function anyObject(string|Closure|null $accessor = null, string $default = null): AnyObjectValue
    {
        return new AnyObjectValue($this->source, $accessor, $default);
    }

    public function array(string|Closure $iterator, string|Closure|null $accessor = null): ArrayValue
    {
        return new ArrayValue($this->source, $iterator, $accessor);
    }

    public function keyedArray(
        string|Closure $key,
        string|Closure $iterator,
        string|Closure|null $accessor = null
    ): KeyedArrayValue {
        return new KeyedArrayValue($this->source, $key, $iterator, $accessor);
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
