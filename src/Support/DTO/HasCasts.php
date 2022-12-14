<?php

namespace Savks\Negotiator\Support\DTO;

use Carbon\CarbonInterface;
use Closure;
use DateTime;
use Savks\Negotiator\Support\DTO\ArrayValue\Item;
use Savks\Negotiator\Support\Mapping\Mapper;

trait HasCasts
{
    public function string(string|Closure|null $accessor = null, string $default = null): StringValue
    {
        return new StringValue($this->source, $accessor, $default);
    }

    public function constString(string $value, bool $asAnyString = false): ConstStringValue
    {
        return new ConstStringValue($value, $asAnyString);
    }

    public function boolean(string|Closure|null $accessor = null, string $default = null): BooleanValue
    {
        return new BooleanValue($this->source, $accessor, $default);
    }

    public function constBoolean(bool $value, bool $asAnyBool = false): ConstBooleanValue
    {
        return new ConstBooleanValue($value, $asAnyBool);
    }

    public function number(string|Closure|null $accessor = null, int|float|null $default = null): NumberValue
    {
        return new NumberValue($this->source, $accessor, $default);
    }

    public function constNumber(int|float $value, bool $asAnyNumber = false): ConstNumberValue
    {
        return new ConstNumberValue($value, $asAnyNumber);
    }

    public function date(string|Closure|null $accessor = null, string|Closure|null $format = null): DateValue
    {
        return new DateValue($this->source, $accessor, $format);
    }

    public function constDate(CarbonInterface|DateTime $value, string|Closure|null $format = null): DateValue
    {
        return new DateValue(null, fn () => $value, $format);
    }

    public function anyObject(string|Closure|null $accessor = null, string $default = null): AnyObjectValue
    {
        return new AnyObjectValue($this->source, $accessor, $default);
    }

    /**
     * @param string|Closure(Item): Value $iterator
     */
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

    public function union(string|Closure|null $accessor = null): UnionType
    {
        return new UnionType($this->source, $accessor);
    }

    public function any(string|Closure|null $accessor = null, mixed $default = null): AnyValue
    {
        return new AnyValue($this->source, $accessor, $default);
    }
}
