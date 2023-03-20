<?php

namespace Savks\Negotiator\Support\DTO;

use Closure;
use ReflectionFunction;
use Savks\Negotiator\Contexts\TypeGenerationContext;
use Savks\Negotiator\Exceptions\UnexpectedValue;
use Savks\Negotiator\Support\Mapping\Mapper;
use Savks\PhpContexts\Context;

use Savks\Negotiator\Support\Types\{
    AliasType,
    AnyType,
    Type,
    Types
};

class MapperValue extends NullableValue
{
    /**
     * @param class-string<Mapper>|Mapper|Closure $mapper
     */
    public function __construct(
        protected readonly mixed $source,
        protected readonly string|Mapper|Closure $mapper,
        protected readonly string|Closure|null $accessor = null
    ) {
    }

    public function resolveMapper(): ?Mapper
    {
        $value = $this->resolveValueFromAccessor(
            $this->accessor,
            $this->source,
            $this->sourcesTrace
        );

        if ($this->accessor && last($this->sourcesTrace) !== $this->source) {
            $this->sourcesTrace[] = $this->source;
        }

        if ($value === null) {
            return null;
        }

        if (\is_string($this->mapper)) {
            $mapper = new ($this->mapper)($value, ...$this->sourcesTrace);
        } else {
            $mapper = $this->mapper instanceof Closure ?
                ($this->mapper)($value, ...$this->sourcesTrace) :
                $this->mapper;
        }

        return $mapper;
    }

    protected function finalize(): mixed
    {
        $mapper = $this->resolveMapper();

        if ($mapper === null) {
            return null;
        }

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
        } elseif (\is_string($this->mapper)) {
            $mapperFQN = $this->mapper;

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
