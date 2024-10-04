<?php

namespace Savks\Negotiator\Support\Mapping\Casts;

use Closure;
use Savks\Negotiator\Contexts\IterationContext;
use Savks\Negotiator\Support\Mapping\Schema;
use stdClass;
use Throwable;

use Savks\Negotiator\Exceptions\{
    InternalException,
    UnexpectedValue
};
use Savks\Negotiator\Support\TypeGeneration\Types\{
    RecordType,
    StringType
};

class KeyedArrayCast extends OptionalCast
{
    use WorkWithOptionalFields;

    /**
     * @var array{
     *     cast: OneOfConstCast|EnumCast|StringCast,
     *     byKey: bool
     * }|OneOfConstCast|EnumCast|StringCast|null
     */
    protected array|OneOfConstCast|EnumCast|StringCast|null $keyBy = null;

    protected bool $optionalIfEmpty = false;

    protected bool $stdClassCastAllowed = false;

    public function __construct(
        protected readonly Cast $cast,
        protected readonly string|Closure|null $accessor = null
    ) {
    }

    public function allowCastStdClass(): static
    {
        $this->stdClassCastAllowed = true;

        return $this;
    }

    public function keyBySchema(OneOfConstCast|EnumCast|StringCast $cast, bool $byKey = false): static
    {
        $this->keyBy = [
            'cast' => $cast,
            'byKey' => $byKey,
        ];

        return $this;
    }

    public function keyBy(string|Closure $accessor): static
    {
        $this->keyBy = [
            'cast' => Schema::string($accessor),
            'byKey' => false,
        ];

        return $this;
    }

    public function keySchema(OneOfConstCast|EnumCast|StringCast $cast): static
    {
        $this->keyBy = $cast;

        return $this;
    }

    public function nullIfEmpty(): static
    {
        $this->optionalIfEmpty = true;

        $this->nullable();

        return $this;
    }

    public function optionalIfEmpty(): static
    {
        $this->optionalIfEmpty = true;

        $this->optional();

        return $this;
    }

    protected function finalize(mixed $source, array $sourcesTrace): ?stdClass
    {
        $value = static::resolveValueFromAccessor(
            $this->accessor,
            $source,
            $sourcesTrace
        );

        if ($this->accessor && last($sourcesTrace) !== $source) {
            $sourcesTrace[] = $source;
        }

        if ($value === null) {
            return null;
        }

        if (! is_iterable($value)) {
            if ($this->stdClassCastAllowed) {
                if (! ($value instanceof stdClass)) {
                    throw new UnexpectedValue(['stdClass', 'iterable'], $value);
                }
            } else {
                throw new UnexpectedValue('iterable', $value);
            }
        }

        $result = new stdClass();

        $index = 0;

        $hasValues = false;

        foreach ($value as $key => $item) {
            if (! $this->keyBy || $this->keyBy instanceof Cast) {
                $keyValue = $this->keyBy instanceof Cast
                    ? $this->keyBy->resolve($key)
                    : (string)$key;
            } else {
                $keyValue = (new IterationContext($index, $key))->wrap(
                    fn () => $this->keyBy['cast']->resolve(
                        $this->keyBy['byKey'] ? $key : $item,
                        array_reverse($sourcesTrace)
                    )
                );
            }

            try {
                $resolvedValue = (new IterationContext($index++, $key))->wrap(
                    fn () => $this->cast->resolve($item, $sourcesTrace)
                );

                if (! $this->needSkip($resolvedValue, $this->cast)) {
                    $result->{$keyValue} = $resolvedValue;

                    $hasValues = true;
                }
            } catch (UnexpectedValue $e) {
                throw UnexpectedValue::wrap($e, "{$key}({$keyValue})");
            } catch (Throwable $e) {
                throw InternalException::wrap($e, "{$key}({$keyValue})");
            }
        }

        return ! $hasValues && $this->optionalIfEmpty ? null : $result;
    }

    protected function types(): RecordType
    {
        if ($this->keyBy instanceof Cast) {
            $cast = $this->keyBy;
        } else {
            $cast = $this->keyBy['cast'] ?? null;
        }

        return new RecordType(
            $cast?->compileTypes() ?? new StringType(),
            $this->cast->compileTypes()
        );
    }
}
