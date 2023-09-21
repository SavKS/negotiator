<?php

namespace Savks\Negotiator\Support\Mapping;

use BackedEnum;
use Closure;
use Savks\Negotiator\Enums\RefTypes;

final class GenericDeclaration
{
    /**
     * @var string|class-string<Mapper>|class-string<BackedEnum>|null
     */
    protected ?string $extends = null;

    public function __construct(public readonly string $name)
    {
    }

    /**
     * @param string|class-string<Mapper>|class-string<BackedEnum> $target
     */
    public function extends(string $target): self
    {
        $this->extends = $target;

        return $this;
    }

    /**
     * @param Closure(RefTypes, string):string|null $refsResolver
     */
    public function stringify(?Closure $refsResolver = null): string
    {
        $extends = $refsResolver ?
            $this->resolveExtends($refsResolver) :
            null;

        if (! $extends) {
            return $this->name;
        }

        return "{$this->name} extends {$extends}";
    }

    /**
     * @param Closure(RefTypes, string):string $refsResolver
     */
    protected function resolveExtends(Closure $refsResolver): ?string
    {
        if (! $this->extends) {
            return null;
        }

        if (is_subclass_of($this->extends, Mapper::class)) {
            return ($refsResolver)(RefTypes::MAPPER, $this->extends);
        } elseif (is_subclass_of($this->extends, BackedEnum::class)) {
            return ($refsResolver)(RefTypes::ENUM, $this->extends);
        }

        return $this->extends;
    }
}
