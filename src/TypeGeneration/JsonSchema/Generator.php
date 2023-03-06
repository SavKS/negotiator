<?php

namespace Savks\Negotiator\TypeGeneration\JsonSchema;

use RuntimeException;
use stdClass;

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

    public function generate(): ?array
    {
        return $this->processType($this->type);
    }

    protected function processType(Type|Types $type): array|stdClass|null
    {
        return match (true) {
            $type instanceof Types => $this->processTypes($type),

            $type instanceof ConstRecordType => $this->processConstRecord($type),
            $type instanceof AnyType => new stdClass,
            $type instanceof BooleanType => [ 'type' => 'boolean' ],
            $type instanceof ConstBooleanType => [ 'type' => 'boolean', 'enum' => [ $type->value ] ],
            $type instanceof StringType => [ 'type' => 'string' ],
            $type instanceof ConstStringType => [ 'type' => 'string', 'enum' => [ $type->value ] ],
            $type instanceof NumberType => [ 'type' => 'number' ],
            $type instanceof ConstNumberType => [ 'type' => 'number', 'enum' => [ $type->value ] ],
            $type instanceof NullType => [ 'type' => 'null' ],
            $type instanceof VoidType => null,
            $type instanceof UndefinedType => null,
            $type instanceof RecordType => [
                'type' => 'object',
                'patternProperties' => [
                    '.*' => $this->processType($type->valueType),
                ],
            ],
            $type instanceof ArrayType => [
                'type' => 'array',
                'items' => $this->processType($type->types),
            ],
            $type instanceof AliasType => [
                '$ref' => $type->alias,
            ],

            default => throw new RuntimeException('Unprocessed type "' . $type::class . '"')
        };
    }

    protected function processTypes(Types $type): array
    {
        if (\count($type->types) === 1) {
            return $this->processType($type->types[0]);
        }

        $parts = \array_filter(
            \array_map(
                $this->processType(...),
                $type->types
            ),
            fn (array|stdClass|null $schema) => $schema !== null
        );

        if (
            \count($parts) === 2 && (
                ($parts[0]['type'] ?? null) === 'null' ||
                ($parts[1]['type'] ?? null) === 'null'
            )
        ) {
            $part = ($parts[0]['type'] ?? null) === 'null' ?
                $parts[1] :
                $parts[0];

            return [
                ...$part,
                'type' => [
                    ...(array) $part['type'],
                    'null',
                ],
            ];
        }

        return [
            $type->asIntersection ? 'allOf' : 'oneOf' => $parts,
        ];
    }

    protected function processConstRecord(ConstRecordType $type): array
    {
        $result = [
            'type' => 'object',
        ];

        foreach ($type->props as $key => $value) {
            $schema = $this->processType($value);
            if ($schema !== null) {
                $result['properties'][$key] = $schema;
            }

            if ($value instanceof Types) {
                $isRequired = true;
                foreach ($value->types as $subType) {
                    if ($subType instanceof UndefinedType) {
                        $isRequired = false;
                    }
                }
            } else {
                $isRequired = true;
            }

            if ($isRequired) {
                $result['required'][] = $key;
            }
        }

        return $result;
    }
}
