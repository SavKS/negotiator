<?php

namespace Savks\Negotiator\Support\Mapping\Casts;

use Closure;
use Savks\Negotiator\Support\Mapping\Casts\LazyCast\LazyCastResolver;
use Savks\Negotiator\Support\TypeGeneration\Types\Type;
use Savks\Negotiator\Support\TypeGeneration\Types\Types;

class LazyCast extends OptionalCast implements ForwardedCast
{
    /**
     * @param Closure(array ...$source):mixed $lazyValueResolver
     */
    public function __construct(
        protected readonly Closure $lazyValueResolver,
        protected readonly Cast $cast,
        protected readonly string|Closure|null $accessor = null
    ) {
    }

    public function nestedCast(): Cast
    {
        return $this->cast;
    }

    protected function finalize(mixed $source, array $sourcesTrace): ?LazyCastResolver
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
            return new LazyCastResolver(
                $value,
                $this->cast,
                [...$sourcesTrace, $source]
            );
        }

        $lazyValue = ($this->lazyValueResolver)(
            $value,
            ...array_reverse($sourcesTrace)
        );

        return new LazyCastResolver(
            $lazyValue,
            $this->cast,
            [...$sourcesTrace, $value]
        );
    }

    protected function types(): Type|Types
    {
        if ($this->cast instanceof OptionalCast
            && $this->cast->optional['value'] === true
        ) {
            $this->cast->optional['asNull'] = true;
        }

        return $this->cast->types();
    }
}
