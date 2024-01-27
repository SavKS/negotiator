<?php

namespace Savks\Negotiator\Support\Mapping\Casts;

use Closure;
use Illuminate\Support\Arr;
use Throwable;

use Savks\Negotiator\Exceptions\{
    DTOException,
    InternalException,
    UnexpectedValue
};
use Savks\Negotiator\Support\TypeGeneration\Types\{
    Type,
    Types
};

class UnionCast extends OptionalCast
{
    /**
     * @var list<array{
     *     'condition': array{string, mixed}|Closure(mixed): bool,
     *     'cast': Cast
     * }>
     */
    protected array $variants = [];

    protected ?Cast $defaultVariant = null;

    public function __construct(protected readonly string|Closure|null $accessor = null)
    {
    }

    public function variant(Closure|array $condition, Cast $cast): static
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

        $i = 0;

        foreach ($this->variants as $variant) {
            if (is_array($variant['condition'])) {
                [$conditionField, $neededConditionFieldValue] = $variant['condition'];

                $passed = data_get($value, $conditionField) === $neededConditionFieldValue;
            } else {
                $passed = $variant['condition'](
                    $value,
                    ...array_reverse($sourcesTrace)
                );
            }

            if (! $passed) {
                $i++;

                continue;
            }

            try {
                $i++;

                return $variant['cast']->resolve($value, $sourcesTrace);
            } catch (UnexpectedValue $e) {
                throw UnexpectedValue::wrap($e, "[Condition #{$i}]", true);
            } catch (Throwable $e) {
                throw InternalException::wrap($e, "[Condition #{$i}]", true);
            }
        }

        if ($this->defaultVariant) {
            try {
                return $this->defaultVariant->resolve($value, $sourcesTrace);
            } catch (UnexpectedValue $e) {
                throw UnexpectedValue::wrap($e, "[Condition #DEFAULT]", true);
            } catch (Throwable $e) {
                throw InternalException::wrap($e, "[Condition #DEFAULT]", true);
            }
        }

        throw new DTOException("Unhandled union type variant for \"{$this->simplifyCondition($value)}\"");
    }

    protected function simplifyCondition(mixed $value): string
    {
        $type = is_object($value) ? $value::class : gettype($value);

        if ($type === 'array') {
            $type = 'array<' . json_encode($value, JSON_UNESCAPED_UNICODE) . '>';
        } elseif ($type === 'object') {
            $type = 'object<' . $value::class . '>';
        }

        return $type;
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
