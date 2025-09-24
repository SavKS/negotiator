<?php

namespace Savks\Negotiator\Support\TypeGeneration\TypeScript;

use Illuminate\Support\Arr;
use RuntimeException;
use Savks\Negotiator\Contexts\TypeGenerationContext;
use Savks\Negotiator\Enums\RefTypes;
use Savks\Negotiator\Support\Mapping\Generic;
use Savks\Negotiator\Support\Mapping\Mapper;
use Savks\Negotiator\Support\TypeGeneration\Types;

class TypeProcessor
{
    public function __construct(protected readonly Types\Type|Types\Types $type)
    {

    }

    public function process(): string
    {
        return $this->processType($this->type);
    }

    protected function processType(Types\Type|Types\Types $type): string
    {
        if ($type instanceof Types\AliasType) {
            if (! $type->generics) {
                return $type->alias;
            }

            $typeGenerationContext = TypeGenerationContext::useSelf();

            $generics = array_map(
                fn (Generic $generic) => $generic->stringify($typeGenerationContext->refsResolver),
                $type->generics
            );

            return sprintf(
                '%s<%s>',
                $type->alias,
                implode(', ', $generics)
            );
        }

        return match (true) {
            $type instanceof Types\Types => sprintf(
                count($type->types) > 1 ? '(%s)' : '%s',
                implode(
                    $type->asIntersection ? ' & ' : ' | ',
                    array_map(
                        fn (Types\Type|Types\Types $type) => $this->processType($type),
                        $type->types
                    )
                )
            ),

            $type instanceof Types\ObjectType => $this->processObjectType($type),
            $type instanceof Types\AnyType => 'any',
            $type instanceof Types\BooleanType => 'boolean',
            $type instanceof Types\ConstBooleanType => $type->value ? 'true' : 'false',
            $type instanceof Types\StringType => 'string',
            $type instanceof Types\ConstStringType => "'{$type->value}'",
            $type instanceof Types\NumberType => 'number',
            $type instanceof Types\ConstNumberType => $type->value,
            $type instanceof Types\NullType => 'null',
            $type instanceof Types\VoidType => 'void',
            $type instanceof Types\UndefinedType => 'undefined',

            $type instanceof Types\RecordType => sprintf(
                'Record<%s, %s>',
                $this->processType($type->keyType),
                $this->processType($type->valueType)
            ),

            $type instanceof Types\ArrayType => "Array<{$this->processType($type->types)}>",

            $type instanceof Types\TupleType => $this->processTupleType($type),

            default => throw new RuntimeException('Unprocessed type "' . $type::class . '"')
        };
    }

    protected function processObjectType(Types\ObjectType $type): string
    {
        $lines = [];

        foreach ($type->props as $key => $type) {
            $isOptional = $this->detectOptional(
                match (true) {
                    $type instanceof Types\Type => [$type],
                    $type instanceof Types\Types => $type->types,
                }
            );

            $optionalSymbol = $isOptional ? '?' : '';

            $lines[] = "'{$key}'{$optionalSymbol}: {$this->processType($type)}";
        }

        return '{' . implode(',', $lines) . '}';
    }

    /**
     * @param array<Types\Type|Types\Types> $types
     */
    protected function detectOptional(array $types): bool
    {
        foreach ($types as $type) {
            if ($type instanceof Types\Types
                && $this->detectOptional($type->types)
            ) {
                return true;
            }

            if ($type instanceof Types\UndefinedType) {
                return true;
            }

            if (
                ! ($type instanceof Types\AliasType)
                || ! $type->ref
                || $type->ref['type'] === RefTypes::ENUM
            ) {
                continue;
            }

            /** @var class-string<Mapper> $mapper */
            $mapper = $type->ref['class'];

            $mapperTypes = $mapper::schema()->compileTypes();

            if ($mapperTypes instanceof Types\Types) {
                foreach ($mapperTypes->types as $mapperType) {
                    if ($mapperType instanceof Types\UndefinedType) {
                        return true;
                    }
                }

                return false;
            }

            return $mapperTypes instanceof Types\UndefinedType;
        }

        return false;
    }

    public function processTupleType(Types\TupleType $type): string
    {
        $types = Arr::map(
            $type->types,
            fn (Types\Types $types) => $this->processType($types)
        );

        if (! $type->restType) {
            return sprintf(
                '[%s]',
                implode(',', $types)
            );
        }

        return sprintf(
            '[%s, ...Array<%s>]',
            implode(',', $types),
            $this->processType($type->restType)
        );
    }
}
