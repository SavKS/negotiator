<?php

namespace Savks\Negotiator\Support\TypeGeneration\TypeScript;

use Illuminate\Support\Arr;
use RuntimeException;
use Savks\Negotiator\Contexts\TypeGenerationContext;
use Savks\Negotiator\Enums\RefTypes;

use Savks\Negotiator\Support\Mapping\{
    Generic,
    Mapper
};
use Savks\Negotiator\Support\TypeGeneration\Types\{
    AliasType,
    AnyType,
    ArrayType,
    BooleanType,
    ConstBooleanType,
    ConstNumberType,
    ConstStringType,
    NullType,
    NumberType,
    ObjectType,
    RecordType,
    StringType,
    TupleType,
    Type,
    Types,
    UndefinedType,
    VoidType
};

class TypeProcessor
{
    public function __construct(protected readonly Type|Types $type)
    {

    }

    public function process(): string
    {
        return $this->processType($this->type);
    }

    protected function processType(Type|Types $type): string
    {
        if ($type instanceof AliasType) {
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
            $type instanceof Types => sprintf(
                count($type->types) > 1 ? '(%s)' : '%s',
                implode(
                    $type->asIntersection ? ' & ' : ' | ',
                    array_map(
                        fn (Type|Types $type) => $this->processType($type),
                        $type->types
                    )
                )
            ),

            $type instanceof ObjectType => $this->processObjectType($type),
            $type instanceof AnyType => 'any',
            $type instanceof BooleanType => 'boolean',
            $type instanceof ConstBooleanType => $type->value ? 'true' : 'false',
            $type instanceof StringType => 'string',
            $type instanceof ConstStringType => "'{$type->value}'",
            $type instanceof NumberType => 'number',
            $type instanceof ConstNumberType => $type->value,
            $type instanceof NullType => 'null',
            $type instanceof VoidType => 'void',
            $type instanceof UndefinedType => 'undefined',

            $type instanceof RecordType => sprintf(
                'Record<%s, %s>',
                $this->processType($type->keyType),
                $this->processType($type->valueType)
            ),

            $type instanceof ArrayType => "Array<{$this->processType($type->types)}>",

            $type instanceof TupleType => sprintf(
                '[%s]',
                implode(
                    ',',
                    Arr::map(
                        $type->types,
                        fn (Types $types) => $this->processType($types)
                    )
                )
            ),

            default => throw new RuntimeException('Unprocessed type "' . $type::class . '"')
        };
    }

    protected function processObjectType(ObjectType $type): string
    {
        $lines = [];

        foreach ($type->props as $key => $type) {
            $isOptional = $this->detectOptional(
                match (true) {
                    $type instanceof Type => [$type],
                    $type instanceof Types => $type->types,
                }
            );

            $optionalSymbol = $isOptional ? '?' : '';

            $lines[] = "'{$key}'{$optionalSymbol}: {$this->processType($type)}";
        }

        return '{' . implode(',', $lines) . '}';
    }

    /**
     * @param list<Type|Types> $types
     */
    protected function detectOptional(array $types): bool
    {
        foreach ($types as $type) {
            if ($type instanceof Types
                && $this->detectOptional($type->types)
            ) {
                return true;
            }

            if ($type instanceof UndefinedType) {
                return true;
            }

            if (
                ! ($type instanceof AliasType)
                || ! $type->ref
                || $type->ref['type'] === RefTypes::ENUM
            ) {
                continue;
            }

            /** @var class-string<Mapper> $mapperFQN */
            $mapperFQN = $type->ref['fqn'];

            if (
                in_array(
                    new UndefinedType(),
                    $mapperFQN::schema()->compileTypes()->types
                )
            ) {
                return true;
            }
        }

        return false;
    }
}
