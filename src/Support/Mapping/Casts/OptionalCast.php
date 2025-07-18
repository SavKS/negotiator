<?php

namespace Savks\Negotiator\Support\Mapping\Casts;

use Closure;
use Illuminate\Support\Arr;
use Savks\Negotiator\Enums\OptionalModes;
use Savks\Negotiator\Exceptions\UnexpectedNull;
use Savks\Negotiator\Support\TypeGeneration\Types\AliasType;
use Savks\Negotiator\Support\TypeGeneration\Types\NullType;
use Savks\Negotiator\Support\TypeGeneration\Types\Types;
use Savks\Negotiator\Support\TypeGeneration\Types\UndefinedType;

abstract class OptionalCast extends Cast
{
    /**
     * @var array{
     *     value: OptionalModes[]|bool,
     *     type?: true,
     *     asNull: bool
     * }
     */
    public array $optional = [
        'value' => false,
        'asNull' => false,
    ];

    public function nullable(array|OptionalModes|null $mode = null): static
    {
        return $this->optional($mode, true);
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

    /**
     * @deprecated Use implicitOptional instead
     */
    public function maybeOptional(bool $asNull = false): static
    {
        return $this->implicitOptional($asNull);
    }

    public function implicitOptional(bool $asNull = false): static
    {
        $this->optional = [
            'value' => false,
            'type' => true,
            'asNull' => $asNull,
        ];

        return $this;
    }

    /**
     * @deprecated Use implicitNullable instead
     */
    public function maybeNullable(bool $asNull = false): static
    {
        return $this->implicitOptional($asNull);
    }

    public function implicitNullable(): static
    {
        return $this->maybeOptional();
    }

    public function resolve(mixed $source, array $sourcesTrace = []): mixed
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
            $forcedType = $this->forcedType instanceof Closure ? ($this->forcedType)() : $this->forcedType;

            $types = is_string($forcedType)
                ? new AliasType($forcedType)
                : $forcedType->types();
        } else {
            $types = $this->types();
        }

        if ($this instanceof ForwardedCast) {
            $nestedCast = $this->nestedCast();

            if ($nestedCast instanceof OptionalCast) {
                $optional = $nestedCast->optional;
            } else {
                $optional['value'] = false;
            }
        } else {
            $optional = $this->optional;
        }

        if (
            $optional['value']
            || ($optional['type'] ?? false) === true
        ) {
            $types = [
                ...Arr::wrap($types),

                $optional['asNull'] ? new NullType() : new UndefinedType(),
            ];
        }

        return new Types(
            Arr::wrap($types)
        );
    }
}
