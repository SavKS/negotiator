<?php

namespace Savks\Negotiator\Support\Mapping\Casts;

use Closure;
use Illuminate\Support\Arr;
use Savks\Negotiator\Exceptions\DTOException;

use Savks\Negotiator\Support\TypeGeneration\Types\{
    Type,
    Types
};

class UnionCast extends NullableCast
{
    /**
     * @var list<array{
     *     'condition': bool|Closure(mixed): bool,
     *     'cast': Cast
     * }>
     */
    protected array $variants = [];

    protected ?Cast $defaultVariant = null;

    public function __construct(protected readonly string|Closure|null $accessor = null)
    {
    }

    public function variant(bool|Closure $condition, Cast $cast): static
    {
        $this->variants[] = [
            'condition' => $condition,
            'cast' => $cast,
        ];

        return $this;
    }

    public function default(Cast $cast): static
    {
        $this->defaultVariant = $cast;

        return $this;
    }

    protected function finalize(mixed $source, array $sourcesTrace): mixed
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

        foreach ($this->variants as $variant) {
            $passed = $variant['condition'](
                $value,
                ...array_reverse($sourcesTrace)
            );

            if (! $passed) {
                continue;
            }

            return $variant['cast']->resolve($value, $sourcesTrace);
        }

        if ($this->defaultVariant) {
            return $this->defaultVariant->resolve($value, $sourcesTrace);
        }

        $type = is_object($value) ? $value::class : gettype($value);

        if ($type === 'array') {
            $type = 'array<' . json_encode($value, JSON_UNESCAPED_UNICODE) . '>';
        } elseif ($type === 'object') {
            $type = 'object<' . $value::class . '>';
        }

        throw new DTOException("Unhandled union type variant for \"{$type}\"");
    }

    protected function types(): Type|Types
    {
        $types = [];

        foreach ($this->variants as $variant) {
            $types[] = $variant['cast']->compileTypes()->types;
        }

        if ($this->defaultVariant) {
            $types[] = $this->defaultVariant->compileTypes()->types;
        }

        $types = Arr::flatten($types);

        return count($types) > 1 ? new Types($types) : head($types);
    }
}
