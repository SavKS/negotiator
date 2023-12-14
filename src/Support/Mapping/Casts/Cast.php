<?php

namespace Savks\Negotiator\Support\Mapping\Casts;

use Illuminate\Support\Arr;
use Savks\Negotiator\Exceptions\UnexpectedNull;

use Savks\Negotiator\Support\TypeGeneration\Types\{
    AliasType,
    Type,
    Types
};

abstract class Cast
{
    use WorkWithAccessor;

    protected array $sourcesTrace = [];

    protected string|Cast|null $forcedType = null;

    public function as(string|Cast $type): static
    {
        $this->forcedType = $type;

        return $this;
    }

    public function resolve(mixed $source, array $sourcesTrace): mixed
    {
        $value = $this->finalize($source, $sourcesTrace);

        if ($value === null) {
            throw new UnexpectedNull('NOT NULL', $value);
        }

        return $value;
    }

    abstract protected function finalize(mixed $source, array $sourcesTrace): mixed;

    public function compileTypes(): Types
    {
        if ($this->forcedType) {
            return is_string($this->forcedType)
                ? new Types([
                    new AliasType($this->forcedType),
                ])
                : $this->forcedType->types();
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
