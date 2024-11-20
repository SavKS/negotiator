<?php

namespace Savks\Negotiator\Support\Mapping\Casts;

use Closure;
use Savks\Negotiator\Support\TypeGeneration\Types\Type;
use Savks\Negotiator\Support\TypeGeneration\Types\Types;

class ScopeCast extends Cast implements ForwardedCast
{
    public function __construct(
        protected readonly Cast $cast,
        protected readonly string|Closure|null $accessor
    ) {
    }

    public function nestedCast(?Closure $callback = null): Cast|static
    {
        if ($callback) {
            $callback(
                $this->nestedCast()
            );

            return $this;
        }

        return $this->cast;
    }

    public function resolve(mixed $source, array $sourcesTrace = []): mixed
    {
        return $this->finalize($source, $sourcesTrace);
    }

    protected function finalize(mixed $source, array $sourcesTrace): mixed
    {
        if ($this->accessor !== null) {
            $value = static::resolveValueFromAccessor(
                $this->accessor,
                $source,
                $sourcesTrace
            );

            if (last($sourcesTrace) !== $source) {
                $sourcesTrace[] = $source;
            }
        } else {
            $value = $source;
        }

        return $this->cast->resolve($value, $sourcesTrace);
    }

    protected function types(): Type|Types
    {
        return $this->cast->compileTypes();
    }
}
