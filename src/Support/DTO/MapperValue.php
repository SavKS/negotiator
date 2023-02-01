<?php

namespace Savks\Negotiator\Support\DTO;

use Closure;
use ReflectionFunction;
use Savks\Negotiator\Contexts\TypeGenerationContext;
use Savks\Negotiator\Support\Mapping\Mapper;
use Savks\Negotiator\TypeGeneration\Faker;
use Savks\PhpContexts\Context;

use Savks\Negotiator\Support\Types\{
    AliasType,
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
        $value = $this->resolveValueFromAccessor($this->accessor, $this->source);

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
            $reflection = new ReflectionFunction($this->mapper);

            $mapperFQN = $reflection->getReturnType()?->getName();

            if ($mapperFQN === 'static' || $mapperFQN === 'self') {
                $mapperFQN = \get_class(
                    $reflection->getClosureThis()
                );
            }

            if (! \is_subclass_of($mapperFQN, Mapper::class)) {
                return new AnyType();
            }
        } else {
            $mapperFQN = $this->mapper::class;
        }

        $mapperRef = Context::use(TypeGenerationContext::class)->resolveMapperRef($mapperFQN);

        if (! $mapperRef) {
            return new AnyType();
        }

        return new AliasType($mapperRef);
    }
}
