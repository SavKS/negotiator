<?php

namespace Savks\Negotiator\Support\DTO;

use Closure;
use Savks\Negotiator\Support\Mapping\Mapper;

class MapperValue extends Value
{
    public function __construct(
        protected readonly mixed $source,
        protected readonly Mapper|Closure $mapper,
        protected readonly string|Closure|null $accessor = null
    ) {
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

        $mapper = $this->mapper instanceof Closure ? ($this->mapper)($value) : $this->mapper;

        $mappedValue = $mapper->map();

        return $mappedValue instanceof Value ? $mappedValue->compile() : $mappedValue;
    }
}
