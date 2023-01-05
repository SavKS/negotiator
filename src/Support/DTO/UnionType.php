<?php

namespace Savks\Negotiator\Support\DTO;

use Closure;
use Illuminate\Support\Arr;
use Savks\Negotiator\Exceptions\DTOException;
use Savks\Negotiator\Support\DTO\Utils\Factory;

use Savks\Negotiator\Support\Types\{
    Type,
    Types
};

class UnionType extends NullableValue
{
    /**
     * @var list<array{
     *     'condition': bool|Closure(mixed): bool,
     *     'callback': Closure(Factory): Value|null
     * }>
     */
    protected array $variants = [];

    public function __construct(
        protected readonly mixed $source,
        protected readonly string|Closure|null $accessor = null
    ) {
    }

    public function variant(bool|Closure $condition, Closure $callback): static
    {
        $this->variants[] = [
            'condition' => $condition,
            'callback' => $callback,
        ];

        return $this;
    }

    protected function finalize(): mixed
    {
        if ($this->accessor === null) {
            $value = $this->source;
        } elseif (\is_string($this->accessor)) {
            $value = \data_get($this->source, $this->accessor);
        } else {
            $value = ($this->accessor)($this->source);
        }

        if ($value === null) {
            return null;
        }

        foreach ($this->variants as $variant) {
            if (! $variant['condition']($value)) {
                continue;
            }

            return $variant['callback'](
                new Factory($value)
            )->finalize();
        }

        $type = \is_object($value) ? $value::class : \gettype($value);

        throw new DTOException("Unhandled union type variant for \"{$type}\"");
    }

    protected function types(): Type|Types
    {
        $types = [];

        foreach ($this->variants as $variant) {
            /** @var Value $value */
            $value = $variant['callback'](
                new Factory(null)
            );

            $types[] = $value->compileTypes()->types;
        }

        $types = Arr::flatten($types);

        return \count($types) > 1 ? new Types($types) : \head($types);
    }
}
