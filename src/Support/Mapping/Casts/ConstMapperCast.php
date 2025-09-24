<?php

namespace Savks\Negotiator\Support\Mapping\Casts;

use Closure;
use Savks\Negotiator\Contexts\TypeGenerationContext;
use Savks\Negotiator\Enums\RefTypes;
use Savks\Negotiator\Exceptions\TypeGenerateException;
use Savks\Negotiator\Exceptions\UnexpectedValue;
use Savks\Negotiator\Support\Mapping\Mapper;
use Savks\Negotiator\Support\TypeGeneration\Types\AliasType;

class ConstMapperCast extends OptionalCast
{
    /**
     * @param class-string<Mapper> $mapper
     */
    public function __construct(
        protected readonly string $mapper,
        protected readonly string|Closure|null $accessor = null
    ) {
    }

    protected function finalize(mixed $source, array $sourcesTrace): ?Mapper
    {
        $value = static::resolveValueFromAccessor(
            $this->accessor,
            $source,
            $sourcesTrace
        );

        if ($value === null) {
            return null;
        }

        if (
            ! ($value instanceof Mapper)
            || $value::class !== $this->mapper
        ) {
            throw new UnexpectedValue("class-string<{$this->mapper}>", $value);
        }

        return $value;
    }

    protected function types(): AliasType
    {
        $mapperRef = TypeGenerationContext::useSelf()->resolveMapperRef($this->mapper);

        if (! $mapperRef) {
            throw new TypeGenerateException("Unknown mapper — \"{$this->mapper}\".");
        }

        return new AliasType($mapperRef, ref: [
            'type' => RefTypes::MAPPER,
            'class' => $this->mapper,
        ]);
    }
}
