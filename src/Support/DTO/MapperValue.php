<?php

namespace Savks\Negotiator\Support\DTO;

use Closure;
use ReflectionFunction;
use ReflectionNamedType;
use Savks\Negotiator\Contexts\TypeGenerationContext;
use Savks\Negotiator\Exceptions\TypeGenerateException;
use Savks\Negotiator\Jit\Jit;
use Savks\Negotiator\Support\Types\AliasType;
use Savks\PhpContexts\Context;

use Savks\Negotiator\Support\Mapping\{
    Generic,
    Mapper
};

class MapperValue extends NullableValue
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
        protected readonly mixed $source,
        protected readonly string|Mapper|Closure $mapper,
        protected readonly string|Closure|null $accessor = null
    ) {
    }

    public function withGenerics(Generic ...$generic): static
    {
        $this->generics = $generic;

        return $this;
    }

    public function resolveCurrentMapper(): ?Mapper
    {
        return static::resolveMapper(
            $this->source,
            $this->mapper,
            $this->accessor,
            $this->sourcesTrace
        );
    }

    protected static function resolveMapper(
        mixed $source,
        string|Mapper|Closure $mapper,
        string|Closure|null $accessor,
        array $sourcesTrace
    ): ?Mapper {
        $value = static::resolveValueFromAccessor(
            $accessor,
            $source,
            $sourcesTrace
        );

        if ($accessor && last($sourcesTrace) !== $source) {
            $sourcesTrace[] = $source;
        }

        if ($value === null) {
            return null;
        }

        if (is_string($mapper)) {
            $mapper = new ($mapper)($value, ...$sourcesTrace);
        } else {
            $mapper = $mapper instanceof Closure ?
                $mapper($value, ...$sourcesTrace) :
                $mapper;
        }

        return $mapper;
    }

    protected function finalize(): mixed
    {
        $mapper = $this->resolveCurrentMapper();

        if ($mapper === null) {
            return null;
        }

        $mappedValue = $mapper->map();

        if ($mappedValue instanceof Jit || $mappedValue instanceof Value) {
            return $mappedValue->compile();
        }

        return $mappedValue;
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

        /** @var TypeGenerationContext $typeGenerationContext */
        $typeGenerationContext = Context::use(TypeGenerationContext::class);

        $mapperRef = $typeGenerationContext->resolveMapperRef($mapperFQN);

        if (! $mapperRef) {
            throw new TypeGenerateException("Unknown mapper — \"{$mapperFQN}\".");
        }

        return $this->generics ?
            new AliasType($mapperRef, $this->generics) :
            new AliasType($mapperRef);
    }

    protected function schema(): array
    {
        return [
            '$$type' => static::class,
            'accessor' => $this->accessor,
            'mapper' => $this->mapper,
        ];
    }

    protected static function finalizeUsingSchema(
        array $schema,
        mixed $source,
        array $sourcesTrace = []
    ): mixed {
        $mapper = static::resolveMapper(
            $source,
            $schema['mapper'],
            $schema['accessor'],
            $sourcesTrace
        );

        return $mapper?->map()->compile();

    }
}
