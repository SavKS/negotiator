<?php

namespace Savks\Negotiator\Support\Mapping\Casts;

use Closure;
use ReflectionFunction;
use ReflectionNamedType;
use Savks\Negotiator\Contexts\TypeGenerationContext;
use Savks\Negotiator\Enums\RefTypes;
use Savks\Negotiator\Exceptions\TypeGenerateException;
use Savks\Negotiator\Support\Mapping\Generic;
use Savks\Negotiator\Support\Mapping\Mapper;
use Savks\Negotiator\Support\TypeGeneration\Types\AliasType;

class MapperCast extends OptionalCast
{
    use CanBeGeneric;

    /**
     * @var Generic[]|null
     */
    protected ?array $generics = null;

    /**
     * @param class-string<Mapper>|Mapper|Closure $mapper
     */
    public function __construct(
        protected readonly string|Mapper|Closure $mapper,
        protected readonly string|Closure|null $accessor = null
    ) {
    }

    public function withGenerics(Generic ...$generic): static
    {
        $this->generics = $generic;

        return $this;
    }

    protected function finalize(mixed $source, array $sourcesTrace): mixed
    {
        return $this->resolveMapper($source, $sourcesTrace)?->resolve();
    }

    public function resolveMapper(mixed $source, array $sourcesTrace): ?Mapper
    {
        $value = static::resolveValueFromAccessor(
            $this->accessor,
            $source,
            $sourcesTrace
        );

        if ($this->accessor && last($sourcesTrace) !== $source) {
            $sourcesTrace[] = $source;
        }

        if ($value === null) {
            return null;
        }

        if (is_string($this->mapper)) {
            if (method_exists($this->mapper, 'factory')) {
                $mapper = $this->mapper::factory(
                    $value,
                    ...array_reverse($sourcesTrace)
                );
            } else {
                $mapper = new ($this->mapper)($value);
            }
        } else {
            $mapper = $this->mapper instanceof Closure ?
                ($this->mapper)(
                    $value,
                    ...array_reverse($sourcesTrace)
                ) :
                $this->mapper;
        }

        return $mapper;
    }

    protected function types(): AliasType
    {
        if ($this->assignedToGeneric) {
            return new AliasType($this->assignedToGeneric);
        }

        if ($this->mapper instanceof Closure) {
            $reflection = new ReflectionFunction($this->mapper);

            $refReturnType = $reflection->getReturnType();

            if (! ($refReturnType instanceof ReflectionNamedType)
                || (
                    ! is_subclass_of($refReturnType->getName(), Mapper::class)
                    && in_array($refReturnType->getName(), ['static', 'self'], true)
                )
            ) {
                throw new TypeGenerateException('The return type in the mapper function must be an mapper.');
            }

            $mapperFQN = $refReturnType->getName();

            if ($mapperFQN === 'static' || $mapperFQN === 'self') {
                $mapperFQN = get_class(
                    $reflection->getClosureThis()
                );
            }

            if (! is_subclass_of($mapperFQN, Mapper::class)) {
                throw new TypeGenerateException('The return type in the mapper function must be an mapper.');
            }
        } elseif (is_string($this->mapper)) {
            $mapperFQN = $this->mapper;

            if (! is_subclass_of($mapperFQN, Mapper::class)) {
                throw new TypeGenerateException('The return type in the mapper function must be an mapper.');
            }
        } else {
            $mapperFQN = $this->mapper::class;
        }

        $mapperRef = TypeGenerationContext::useSelf()->resolveMapperRef($mapperFQN);

        if (! $mapperRef) {
            throw new TypeGenerateException("Unknown mapper — \"{$mapperFQN}\".");
        }

        return $this->generics ?
            new AliasType($mapperRef, $this->generics, [
                'type' => RefTypes::MAPPER,
                'fqn' => $mapperFQN,
            ]) :
            new AliasType($mapperRef, ref: [
                'type' => RefTypes::MAPPER,
                'fqn' => $mapperFQN,
            ]);
    }
}
