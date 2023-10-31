<?php

namespace Savks\Negotiator\Support\Mapping\Casts;

use Closure;

use Savks\Negotiator\Support\TypeGeneration\Types\{
    Type,
    Types
};

class ScopeCast extends Cast
{
    public function __construct(
        protected readonly Cast $cast,
        protected readonly string|Closure $accessor
    ) {
    }

    protected function finalize(mixed $source, array $sourcesTrace): ?array
    {
        $value = static::resolveValueFromAccessor(
            $this->accessor,
            $source,
            $sourcesTrace
        );

        if ($this->accessor && last($sourcesTrace) !== $source) {
            $sourcesTrace[] = $source;
        }

        return $this->cast->resolve($value, $sourcesTrace);
    }

    protected function types(): Type|Types
    {
        return $this->cast->compileTypes();
    }
}
