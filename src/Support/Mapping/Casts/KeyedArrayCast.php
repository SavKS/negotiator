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
    /**
     * @var array{
     *     cast: OneOfConstCast|EnumCast|StringCast,
     *     byKey: bool
     * }|null
     */
    protected array|null $keyBy = null;

    protected bool $nullIfEmpty = false;

    public function __construct(
        protected readonly Cast $cast,
        protected readonly string|Closure|null $accessor = null
    ) {
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

    public function nullIfEmpty(): static
    {
        $this->nullIfEmpty = true;

        $this->nullable();

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
            throw new UnexpectedValue('iterable', $value);
        }

        $result = new stdClass();

        $index = 0;

        $hasValues = false;

        foreach ($value as $key => $item) {
            if (! $this->keyBy) {
                $keyValue = (string)$key;
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

                $skip = $resolvedValue === null
                    && $this->cast instanceof OptionalCast
                    && $this->cast->optional['value']
                    && ! $this->cast->optional['asNull'];

                if (! $skip) {
                    $result->{$keyValue} = $resolvedValue;

                    $hasValues = true;
                }
            } catch (UnexpectedValue $e) {
                throw UnexpectedValue::wrap($e, "{$key}({$keyValue})");
            } catch (Throwable $e) {
                throw InternalException::wrap($e, "{$key}({$keyValue})");
            }
        }

        return ! $hasValues && $this->nullIfEmpty ? null : $result;
    }

    protected function types(): RecordType
    {
        $cast = $this->keyBy['cast'] ?? null;

        return new RecordType(
            $cast?->compileTypes() ?? new StringType(),
            $this->cast->compileTypes()
        );
    }
}
