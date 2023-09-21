<?php

namespace Savks\Negotiator\Support\Mapping;

use BackedEnum;
use Closure;
use Savks\Negotiator\Enums\RefTypes;

final class Generic
{
    /**
     * @param string|class-string<Mapper>|class-string<BackedEnum> $type
     */
    public function __construct(public readonly string $type)
    {
    }

    /**
     * @param Closure(RefTypes, string):string|null $refsResolver
     */
    public function stringify(?Closure $refsResolver = null): string
    {
        if (is_subclass_of($this->type, Mapper::class)) {
            return ($refsResolver)(RefTypes::MAPPER, $this->type);
        } elseif (is_subclass_of($this->type, BackedEnum::class)) {
            return ($refsResolver)(RefTypes::ENUM, $this->type);
        }

        return $this->type;
    }
}
