<?php

namespace Savks\Negotiator\Support\Mapping\Casts;

use Closure;
use Savks\Negotiator\Contexts\IterationContext;
use Savks\Negotiator\Exceptions\InternalException;
use Savks\Negotiator\Exceptions\UnexpectedValue;
use Savks\Negotiator\Support\Mapping\Schema;
use Savks\Negotiator\Support\TypeGeneration\Types\RecordType;
use Savks\Negotiator\Support\TypeGeneration\Types\StringType;
use stdClass;
use Throwable;

class RecordCast extends OptionalCast
{
    use WorkWithOptionalFields;

    /**
     * @var array{
     *     cast: OneOfConstCast|EnumCast|StringCast|MapperCast,
     *     byKey: bool
     * }|OneOfConstCast|EnumCast|StringCast|MapperCast|null
     */
    protected array|OneOfConstCast|EnumCast|StringCast|MapperCast|null $keyBy = null;

    protected bool $optionalIfEmpty = false;

    protected bool $stdClassCastAllowed = false;

    protected bool $associative = false;

    public function __construct(
        protected readonly Cast $cast,
        protected readonly string|Closure|null $accessor = null
    ) {
    }

    public function associative(): static
    {
        $this->associative = true;

        return $this;
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

    public function keySchema(OneOfConstCast|EnumCast|StringCast|MapperCast $cast): static
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

    /**
     * @return stdClass|array<array-key, mixed>|null
     *
     * @throws Throwable
     */
    protected function finalize(mixed $source, array $sourcesTrace): stdClass|array|null
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

        /** @var iterable<array-key, mixed> $value */
        foreach ($value as $key => $item) {
            if (! $this->keyBy || $this->keyBy instanceof Cast) {
                /** @var string $keyValue */
                $keyValue = $this->keyBy instanceof Cast
                    ? $this->keyBy->resolve($key)
                    : (string)$key;
            } else {
                /** @var string $keyValue */
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

        if (
            ! $hasValues
            && ($this->optionalIfEmpty || $this->associative)
        ) {
            return null;
        }

        return $this->associative ? (array)$result : $result;
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
