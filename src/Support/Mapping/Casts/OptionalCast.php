<?php

namespace Savks\Negotiator\Support\Mapping\Casts;

use Illuminate\Support\Arr;
use Savks\Negotiator\Enums\OptionalModes;
use Savks\Negotiator\Exceptions\UnexpectedNull;

use Savks\Negotiator\Support\TypeGeneration\Types\{
    AliasType,
    NullType,
    Types,
    UndefinedType
};

abstract class OptionalCast extends Cast
{
    public bool $nullable = false;

    /**
     * @var array{
     *     value: OptionalModes[]|bool,
     *     asNull: bool
     * }
     */
    public array $optional = [
        'value' => false,
        'asNull' => false,
    ];

    public function nullable(): static
    {
        return $this->optional(asNull: true);
    }

    /**
     * @param OptionalModes[]|OptionalModes|null $mode
     */
    public function optional(array|OptionalModes|null $mode = null, bool $asNull = false): static
    {
        $this->optional = [
            'value' => $mode === null || $mode === [] ? true : Arr::wrap($mode),
            'asNull' => $asNull,
        ];

        return $this;
    }

    public function optionalIfFalse(): static
    {
        return $this->optional(OptionalModes::FALSE_AS_OPTIONAL);
    }

    public function resolve(mixed $source, array $sourcesTrace): mixed
    {
        $value = $this->finalize($source, $sourcesTrace);

        if (! $value && $this->optional['value'] !== false) {
            $modes = $this->optional['value'] === true
                ? []
                : Arr::wrap($this->optional['value']);

            if (
                (
                    in_array(OptionalModes::FALSE_AS_OPTIONAL, $modes)
                    && $value === false
                )
                || (
                    in_array(OptionalModes::EMPTY_STRING_AS_OPTIONAL, $modes)
                    && $value === ''
                )
                || (
                    in_array(OptionalModes::EMPTY_ARRAY_AS_OPTIONAL, $modes)
                    && $value === []
                )
            ) {
                $value = null;
            }
        }


        if ($value === null && ! $this->optional['value']) {
            throw new UnexpectedNull('NOT NULL', $value);
        }

        return $value;
    }

    public function compileTypes(): Types
    {
        if ($this->forcedType) {
            $types = is_string($this->forcedType)
                ? new AliasType($this->forcedType)
                : $this->forcedType->types();
        } else {
            $types = $this->types();
        }

        if ($this->optional['value']) {
            $types = [
                ...Arr::wrap($types),

                $this->optional['asNull'] ? new NullType() : new UndefinedType(),
            ];
        }

        return new Types(
            Arr::wrap($types)
        );
    }
}
