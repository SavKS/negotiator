<?php

namespace Savks\Negotiator\Support\DTO;

use BackedEnum;
use Closure;
use Illuminate\Support\Traits\Macroable;
use Savks\Negotiator\Support\DTO\ArrayValue\Item;
use Savks\Negotiator\Support\Mapping\Mapper;

use Savks\Negotiator\Support\DTO\Utils\{
    Intersection,
    Spread
};

abstract class Castable
{
    use Macroable;

    public function __construct(
        protected readonly mixed $source,
        protected readonly array $sourcesTrace = [],
    ) {
    }

    public function string(string|Closure|null $accessor = null, string $default = null): StringValue
    {
        return (new StringValue($this->source, $accessor, $default))->setSourcesTrace(
            $this->sourcesTrace
        );
    }

    public function constString(string $value, bool $asAnyString = false): ConstStringValue
    {
        return new ConstStringValue($value, $asAnyString);
    }

    public function boolean(string|Closure|null $accessor = null, bool $default = null): BooleanValue
    {
        return (new BooleanValue($this->source, $accessor, $default))->setSourcesTrace(
            $this->sourcesTrace
        );
    }

    public function constBoolean(bool $value, bool $asAnyBool = false): ConstBooleanValue
    {
        return new ConstBooleanValue($value, $asAnyBool);
    }

    public function number(string|Closure|null $accessor = null, int|float|null $default = null): NumberValue
    {
        return (new NumberValue($this->source, $accessor, $default))->setSourcesTrace(
            $this->sourcesTrace
        );
    }

    public function constNumber(int|float $value, bool $asAnyNumber = false): ConstNumberValue
    {
        return new ConstNumberValue($value, $asAnyNumber);
    }

    public function anyObject(string|Closure|null $accessor = null, string $default = null): AnyObjectValue
    {
        return (new AnyObjectValue($this->source, $accessor, $default))->setSourcesTrace(
            $this->sourcesTrace
        );
    }

    /**
     * @param string|Closure(Item): Value $iterator
     */
    public function array(string|Closure $iterator, string|Closure|null $accessor = null): ArrayValue
    {
        return (new ArrayValue($this->source, $iterator, $accessor))->setSourcesTrace(
            $this->sourcesTrace
        );
    }

    public function keyedArray(
        string|Closure $key,
        string|Closure $iterator,
        string|Closure|null $accessor = null
    ): KeyedArrayValue {
        return (new KeyedArrayValue($this->source, $key, $iterator, $accessor))->setSourcesTrace(
            $this->sourcesTrace
        );
    }

    public function object(Closure $callback, string|Closure|null $accessor = null): ObjectValue
    {
        return (new ObjectValue($this->source, $callback, $accessor))->setSourcesTrace(
            $this->sourcesTrace
        );
    }

    /**
     * @param class-string<Mapper>|Mapper|Closure $mapper
     */
    public function mapper(string|Mapper|Closure $mapper, string|Closure|null $accessor = null): MapperValue
    {
        return (new MapperValue($this->source, $mapper, $accessor))->setSourcesTrace(
            $this->sourcesTrace
        );
    }

    public function union(string|Closure|null $accessor = null): UnionType
    {
        return (new UnionType($this->source, $accessor))->setSourcesTrace(
            $this->sourcesTrace
        );
    }

    public function any(string|Closure|null $accessor = null, mixed $default = null): AnyValue
    {
        return (new AnyValue($this->source, $accessor, $default))->setSourcesTrace(
            $this->sourcesTrace
        );
    }

    public function spread(Closure $callback, string|Closure|null $accessor = null): Spread
    {
        return (new Spread($this->source, $callback, $accessor))->setSourcesTrace(
            $this->sourcesTrace
        );
    }

    public function intersection(Value|Mapper ...$objects): Intersection
    {
        return new Intersection(...$objects);
    }

    /**
     * @param class-string<BackedEnum> $enum
     */
    public function enum(string $enum, string|Closure|null $accessor = null): EnumValue
    {
        return (new EnumValue($this->source, $enum, $accessor))->setSourcesTrace(
            $this->sourcesTrace
        );
    }

    public function constEnum(BackedEnum $case): ConstEnumValue
    {
        return new ConstEnumValue($case);
    }

    public function null(): NullValue
    {
        return new NullValue();
    }
}
