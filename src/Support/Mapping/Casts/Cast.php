<?php

namespace Savks\Negotiator\Support\Mapping\Casts;

use Closure;
use Illuminate\Support\Arr;
use Savks\Negotiator\Exceptions\UnexpectedNull;
use Savks\Negotiator\Support\TypeGeneration\Types\AliasType;
use Savks\Negotiator\Support\TypeGeneration\Types\Type;
use Savks\Negotiator\Support\TypeGeneration\Types\Types;

abstract class Cast
{
    use WorkWithAccessor;

    /**
     * @var list<mixed> $sourcesTrace
     */
    protected array $sourcesTrace = [];

    /**
     * @var string|Cast|Closure():(string|Cast)|null $forcedType
     */
    protected string|Cast|Closure|null $forcedType = null;

    /**
     * @param string|Cast|Closure():(string|Cast) $type
     *
     * @return $this
     */
    public function as(string|Cast|Closure $type): static
    {
        $this->forcedType = $type;

        return $this;
    }

    /**
     * @param list<mixed> $sourcesTrace
     */
    public function resolve(mixed $source, array $sourcesTrace = []): mixed
    {
        $value = $this->finalize($source, $sourcesTrace);

        if ($value === null) {
            throw new UnexpectedNull('NOT NULL', $value);
        }

        return $value;
    }

    /**
     * @param list<mixed> $sourcesTrace
     */
    abstract protected function finalize(mixed $source, array $sourcesTrace): mixed;

    public function compileTypes(): Types
    {
        if ($this->forcedType) {
            $forcedType = $this->forcedType instanceof Closure ? ($this->forcedType)() : $this->forcedType;

            return is_string($forcedType)
                ? new Types([
                    new AliasType($forcedType),
                ])
                : $forcedType->types();
        }

        return new Types(
            Arr::wrap(
                $this->types()
            )
        );
    }

    abstract protected function types(): Type|Types;

    public function setSourcesTrace(mixed $trace): static
    {
        $this->sourcesTrace = [...$this->sourcesTrace, ...$trace];

        return $this;
    }
}
