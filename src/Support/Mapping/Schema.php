<?php

namespace Savks\Negotiator\Support\Mapping;

use BackedEnum;
use Closure;
use Illuminate\Support\Traits\Macroable;
use LogicException;
use Savks\Negotiator\Contexts\IterationContext;
use Savks\Negotiator\Support\Mapping\Casts\ObjectUtils\Spread;
use Savks\Negotiator\Support\Mapping\Casts\ObjectUtils\TypedField;

class Schema
{
    use Macroable;

    public static function string(string|Closure|null $accessor = null, ?string $default = null): Casts\StringCast
    {
        return new Casts\StringCast($accessor, $default);
    }

    public static function iterationIndex(int $increase = 0): Casts\NumberCast
    {
        return self::number(function () use ($increase) {
            $iterationContext = IterationContext::tryUseSelf();

            if (! $iterationContext) {
                throw new LogicException('The method "iterationIndex" works only in array or keyedArray casts');
            }

            return $iterationContext->index + $increase;
        });
    }

    public static function iterationKey(?Casts\Cast $keySchema = null): Casts\Cast
    {
        $keySchema ??= self::string();

        return self::scope($keySchema, function () {
            $iterationContext = IterationContext::tryUseSelf();

            if (! $iterationContext) {
                throw new LogicException('The method "iterationKey" works only in array or keyedArray casts');
            }

            return $iterationContext->key;
        });
    }

    public static function number(
        string|Closure|null $accessor = null,
        int|float|Closure|null $default = null
    ): Casts\NumberCast {
        return new Casts\NumberCast($accessor, $default);
    }

    /**
     * @param array<string, Casts\Cast>|Spread[]|TypedField[] $schema
     */
    public static function object(array $schema, string|Closure|null $accessor = null): Casts\ObjectCast
    {
        return new Casts\ObjectCast($schema, $accessor);
    }

    public static function constString(string $value, bool $asAnyString = false): Casts\ConstStringCast
    {
        return new Casts\ConstStringCast($value, $asAnyString);
    }

    public static function boolean(string|Closure|null $accessor = null, ?bool $default = null): Casts\BooleanCast
    {
        return new Casts\BooleanCast($accessor, $default);
    }

    public static function constBoolean(bool $value, bool $asAnyBool = false): Casts\ConstBooleanCast
    {
        return new Casts\ConstBooleanCast($value, $asAnyBool);
    }

    public static function constNumber(int|float $value, bool $asAnyNumber = false): Casts\ConstNumberCast
    {
        return new Casts\ConstNumberCast($value, $asAnyNumber);
    }

    public static function anyObject(
        string|Closure|null $accessor = null,
        array|object|null $default = null
    ): Casts\AnyObjectCast {
        return new Casts\AnyObjectCast($accessor, $default);
    }

    public static function array(Casts\Cast $cast, string|Closure|null $accessor = null): Casts\ArrayCast
    {
        return new Casts\ArrayCast($cast, $accessor);
    }

    /**
     * @deprecated use record instead
     */
    public static function keyedArray(Casts\Cast $cast, string|Closure|null $accessor = null): Casts\KeyedArrayCast
    {
        return new Casts\KeyedArrayCast($cast, $accessor);
    }

    public static function record(Casts\Cast $cast, string|Closure|null $accessor = null): Casts\RecordCast
    {
        return new Casts\RecordCast($cast, $accessor);
    }

    /**
     * @param class-string<Mapper>|Mapper|Closure $mapper
     */
    public static function mapper(string|Mapper|Closure $mapper, string|Closure|null $accessor = null): Casts\MapperCast
    {
        return new Casts\MapperCast($mapper, $accessor);
    }

    /**
     * @param class-string<Mapper> $mapper
     */
    public static function constMapper(string $mapper, string|Closure|null $accessor = null): Casts\ConstMapperCast
    {
        return new Casts\ConstMapperCast($mapper, $accessor);
    }

    public static function union(string|Closure|null $accessor = null): Casts\UnionCast
    {
        return new Casts\UnionCast($accessor);
    }

    public static function any(string|Closure|null $accessor = null, mixed $default = null): Casts\AnyCast
    {
        return new Casts\AnyCast($accessor, $default);
    }

    public static function intersection(Casts\Cast|Mapper ...$objects): Casts\IntersectionCast
    {
        return new Casts\IntersectionCast(...$objects);
    }

    /**
     * @param array<Casts\Cast|Mapper> $casts
     */
    public static function tuple(array $casts, string|Closure|null $accessor = null): Casts\TupleCast
    {
        return new Casts\TupleCast($casts, $accessor);
    }

    /**
     * @param class-string<BackedEnum> $enum
     */
    public static function enum(
        string $enum,
        string|Closure|null $accessor = null,
        ?BackedEnum $defaultValue = null
    ): Casts\EnumCast {
        return new Casts\EnumCast($enum, $accessor, $defaultValue);
    }

    public static function constEnum(BackedEnum $case): Casts\ConstEnumCast
    {
        return new Casts\ConstEnumCast($case);
    }

    public static function null(): Casts\NullCast
    {
        return new Casts\NullCast();
    }

    public static function undefined(): Casts\UndefinedCast
    {
        return new Casts\UndefinedCast();
    }

    /**
     * @param Casts\ConstCast[] $values
     */
    public static function oneOfConst(array $values, string|Closure|null $accessor = null): Casts\OneOfConstCast
    {
        return new Casts\OneOfConstCast($values, $accessor);
    }

    public static function scope(Casts\Cast $cast, string|Closure|null $accessor): Casts\ScopeCast
    {
        return new Casts\ScopeCast($cast, $accessor);
    }

    public static function lazy(
        Closure $lazyValueResolver,
        Casts\Cast $schema,
        string|Closure|null $accessor = null
    ): Casts\LazyCast {
        return new Casts\LazyCast($lazyValueResolver, $schema, $accessor);
    }
}
