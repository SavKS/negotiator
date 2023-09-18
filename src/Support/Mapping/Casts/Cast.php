<?php

namespace Savks\Negotiator\Support\Mapping\Casts;

use Illuminate\Support\Arr;
use Savks\Negotiator\Exceptions\UnexpectedNull;

use Savks\Negotiator\Support\TypeGeneration\Types\{
    Type,
    Types
};

abstract class Cast
{
    use WorkWithAccessor;

    protected array $sourcesTrace = [];

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
