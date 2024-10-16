<?php

namespace Savks\Negotiator\Support\Mapping;

use BackedEnum;
use Closure;
use Illuminate\Support\Traits\Macroable;
use LogicException;
use Savks\Negotiator\Contexts\IterationContext;

use Savks\Negotiator\Support\Mapping\Casts\{
    ObjectUtils\Spread,
    ObjectUtils\TypedField,
    AnyCast,
    AnyObjectCast,
    ArrayCast,
    BooleanCast,
    Cast,
    ConstBooleanCast,
    ConstCast,
    ConstEnumCast,
    ConstNumberCast,
    ConstStringCast,
    EnumCast,
    IntersectionCast,
    KeyedArrayCast,
    LazyCast,
    MapperCast,
    NullCast,
    NumberCast,
    ObjectCast,
    OneOfConstCast,
    ScopeCast,
    StringCast,
    TupleCast,
    UnionCast
};

class Schema
{
    use Macroable;

    public static function string(string|Closure|null $accessor = null, ?string $default = null): StringCast
    {
        return new StringCast($accessor, $default);
    }

    public static function iterationIndex(int $increase = 0): NumberCast
    {
        return self::number(function () use ($increase) {
            $iterationContext = IterationContext::tryUseSelf();

            if (! $iterationContext) {
                throw new LogicException('The method "iterationIndex" works only in array or keyedArray casts');
            }

            return $iterationContext->index + $increase;
        });
    }

    public static function iterationKey(?Cast $keySchema = null): Cast
    {
        $keySchema ??= self::string();

        return self::scope($keySchema, function () {
            $iterationContext = IterationContext::tryUseSelf();

            if (! $iterationContext) {
                throw new LogicException('The method "iterationIndex" works only in array or keyedArray casts');
            }

            return $iterationContext->key;
        });
    }

    public static function number(
        string|Closure|null $accessor = null,
        int|float|Closure|null $default = null
    ): NumberCast {
        return new NumberCast($accessor, $default);
    }

    /**
     * @param array<string, Cast>|Spread[]|TypedField[] $schema
     */
    public static function object(array $schema, string|Closure|null $accessor = null): ObjectCast
    {
        return new ObjectCast($schema, $accessor);
    }

    public static function constString(string $value, bool $asAnyString = false): ConstStringCast
    {
        return new ConstStringCast($value, $asAnyString);
    }

    public static function boolean(string|Closure|null $accessor = null, ?bool $default = null): BooleanCast
    {
        return new BooleanCast($accessor, $default);
    }

    public static function constBoolean(bool $value, bool $asAnyBool = false): ConstBooleanCast
    {
        return new ConstBooleanCast($value, $asAnyBool);
    }

    public static function constNumber(int|float $value, bool $asAnyNumber = false): ConstNumberCast
    {
        return new ConstNumberCast($value, $asAnyNumber);
    }

    public static function anyObject(
        string|Closure|null $accessor = null,
        array|object|null $default = null
    ): AnyObjectCast {
        return new AnyObjectCast($accessor, $default);
    }

    public static function array(Cast $cast, string|Closure|null $accessor = null): ArrayCast
    {
        return new ArrayCast($cast, $accessor);
    }

    public static function keyedArray(Cast $cast, string|Closure|null $accessor = null): KeyedArrayCast
    {
        return new KeyedArrayCast($cast, $accessor);
    }

    /**
     * @param class-string<Mapper>|Mapper|Closure $mapper
     */
    public static function mapper(string|Mapper|Closure $mapper, string|Closure|null $accessor = null): MapperCast
    {
        return new MapperCast($mapper, $accessor);
    }

    public static function union(string|Closure|null $accessor = null): UnionCast
    {
        return new UnionCast($accessor);
    }

    public static function any(string|Closure|null $accessor = null, mixed $default = null): AnyCast
    {
        return new AnyCast($accessor, $default);
    }

    public static function intersection(Cast|Mapper ...$objects): IntersectionCast
    {
        return new IntersectionCast(...$objects);
    }

    /**
     * @param list<Cast|Mapper> $casts
     */
    public static function tuple(array $casts, string|Closure|null $accessor = null): TupleCast
    {
        return new TupleCast($casts, $accessor);
    }

    /**
     * @param class-string<BackedEnum> $enum
     */
    public static function enum(
        string $enum,
        string|Closure|null $accessor = null,
        ?BackedEnum $defaultValue = null
    ): EnumCast {
        return new EnumCast($enum, $accessor, $defaultValue);
    }

    public static function constEnum(BackedEnum $case): ConstEnumCast
    {
        return new ConstEnumCast($case);
    }

    public static function null(): NullCast
    {
        return new NullCast();
    }

    /**
     * @param ConstCast[] $values
     */
    public static function oneOfConst(array $values, string|Closure|null $accessor = null): OneOfConstCast
    {
        return new OneOfConstCast($values, $accessor);
    }

    public static function scope(Cast $cast, string|Closure|null $accessor): ScopeCast
    {
        return new ScopeCast($cast, $accessor);
    }

    public static function lazy(Closure $lazyValueResolver, Cast $schema): LazyCast
    {
        return new LazyCast($lazyValueResolver, $schema);
    }
}
