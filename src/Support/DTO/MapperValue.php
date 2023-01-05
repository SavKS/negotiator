<?php

namespace Savks\Negotiator\Support\DTO;

use Closure;
use ReflectionFunction;
use Savks\Negotiator\Support\Mapping\Mapper;
use Savks\Negotiator\TypeGeneration\Faker;

use Savks\Negotiator\Support\Types\{
    AnyType,
    Type,
    Types
};

class MapperValue extends NullableValue
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

    protected function types(): Type|Types
    {
        if ($this->mapper instanceof Closure) {
            $ref = new ReflectionFunction($this->mapper);

            $mapperFQN = $ref->getReturnType()?->getName();

            if (! \is_subclass_of($mapperFQN, Mapper::class)) {
                return new AnyType();
            }
        } else {
            $mapperFQN = $this->mapper::class;
        }

        $mapper = (new Faker())->makeMapper($mapperFQN);

        return $mapper->map()->compileTypes();
    }
}
