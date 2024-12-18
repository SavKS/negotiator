<?php

namespace Savks\Negotiator\Support\Mapping\Casts;

use Closure;
use Savks\Negotiator\Exceptions\UnexpectedValue;
use Savks\Negotiator\Support\Mapping\Casts\LazyCast\LazyCastResolver;
use Savks\Negotiator\Support\TypeGeneration\Types\Type;
use Savks\Negotiator\Support\TypeGeneration\Types\Types;
use Shelter\Utils\Support\LazyResolve\LazyValue;

class LazyCast extends OptionalCast implements ForwardedCast
{
    /**
     * @param Closure(array ...$source): (LazyValue|null) $lazyValueResolver
     */
    public function __construct(
        protected readonly Closure $lazyValueResolver,
        protected readonly Cast $cast,
    ) {
    }

    public function nestedCast(): Cast
    {
        return $this->cast;
    }

    protected function finalize(mixed $source, array $sourcesTrace): ?LazyCastResolver
    {
        $lazyValue = ($this->lazyValueResolver)(
            $source,
            ...array_reverse($sourcesTrace)
        );

        if ($lazyValue !== null && ! ($lazyValue instanceof LazyValue)) {
            throw new UnexpectedValue([LazyValue::class], $lazyValue);
        }

        return new LazyCastResolver(
            $lazyValue,
            $this->cast,
            [...$sourcesTrace, $source]
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
