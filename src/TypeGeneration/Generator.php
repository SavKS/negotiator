<?php

namespace Savks\Negotiator\TypeGeneration;

use RuntimeException;

use Savks\Negotiator\Support\Types\{
    AliasType,
    AnyType,
    ArrayType,
    BooleanType,
    ConstBooleanType,
    ConstNumberType,
    ConstRecordType,
    ConstStringType,
    NullType,
    NumberType,
    RecordType,
    StringType,
    Type,
    Types,
    UndefinedType,
    VoidType
};

class Generator
{
    public function __construct(protected readonly Type|Types $type)
    {

    }

    public function generate(): string
    {
        return $this->processType($this->type);
    }

    protected function processType(Type|Types $type): string
    {
        return match (true) {
            $type instanceof Types => \sprintf(
                \count($type->types) > 1 ? '(%s)' : '%s',
                \implode(
                    $type->asIntersection ? ' & ' : ' | ',
                    \array_map(
                        fn (Type|Types $type) => $this->processType($type),
                        $type->types
                    )
                )
            ),

            $type instanceof ConstRecordType => $this->processConstRecord($type),
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
            $type instanceof ArrayType => 'Array<' . $this->processType($type->types) . '>',
            $type instanceof AliasType => $type->alias,

            default => throw new RuntimeException('Unprocessed type "' . $type::class . '"')
        };
    }

    protected function processConstRecord(ConstRecordType $type): string
    {
        $lines = [];

        foreach ($type->props as $key => $type) {
            $lines[] = "'{$key}': {$this->processType($type)}";
        }

        return '{' . \implode(',', $lines) . '}';
    }
}
