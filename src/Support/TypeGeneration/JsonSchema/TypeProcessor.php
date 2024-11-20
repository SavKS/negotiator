<?php

namespace Savks\Negotiator\Support\TypeGeneration\JsonSchema;

use RuntimeException;
use Savks\Negotiator\Support\TypeGeneration\Types;

class TypeProcessor
{
    public function __construct(protected readonly Types\Type|Types\Types $type)
    {
    }

    public function process(): ?array
    {
        return $this->processType($this->type);
    }

    protected function processType(Types\Type|Types\Types $type): ?array
    {
        return match (true) {
            $type instanceof Types\Types => $this->processTypes($type),

            $type instanceof Types\ObjectType => $this->processObjectType($type),

            $type instanceof Types\AnyType => [
                'type' => 'any',
            ],

            $type instanceof Types\BooleanType => [
                'type' => 'boolean',
            ],

            $type instanceof Types\ConstBooleanType => [
                'type' => 'boolean',
                'enum' => [$type->value],
            ],

            $type instanceof Types\StringType => [
                'type' => 'string',
            ],

            $type instanceof Types\ConstStringType => [
                'type' => 'string',
                'enum' => [$type->value],
            ],

            $type instanceof Types\NumberType => [
                'type' => 'number',
            ],

            $type instanceof Types\ConstNumberType => [
                'type' => 'number',
                'enum' => [$type->value],
            ],

            $type instanceof Types\NullType => [
                'type' => 'null',
            ],

            $type instanceof Types\VoidType,
            $type instanceof Types\UndefinedType => null,

            $type instanceof Types\RecordType => [
                'type' => 'object',
                'patternProperties' => [
                    '.*' => $this->processType($type->valueType),
                ],
            ],

            $type instanceof Types\ArrayType => [
                'type' => 'array',
                'items' => $this->processType($type->types),
            ],

            $type instanceof Types\AliasType => [
                '$ref' => $type->alias,
            ],

            default => throw new RuntimeException('Unprocessed type "' . $type::class . '"')
        };
    }

    protected function processTypes(Types\Types $type): array
    {
        if (count($type->types) === 1) {
            return $this->processType($type->types[0]);
        }

        $parts = array_filter(
            array_map(
                $this->processType(...),
                $type->types
            ),
            fn (?array $schema) => $schema !== null
        );

        if (
            count($parts) === 2 && (
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
                    ...(array)$part['type'],
                    'null',
                ],
            ];
        }

        return [
            $type->asIntersection ? 'allOf' : 'oneOf' => $parts,
        ];
    }

    protected function processObjectType(Types\ObjectType $type): array
    {
        $result = [
            'type' => 'object',
        ];

        foreach ($type->props as $key => $value) {
            $schema = $this->processType($value);
            if ($schema !== null) {
                $result['properties'][$key] = $schema;
            }

            $isRequired = true;

            if ($value instanceof Types\Types) {
                foreach ($value->types as $subType) {
                    if ($subType instanceof Types\UndefinedType) {
                        $isRequired = false;
                    }
                }
            }

            if ($isRequired) {
                $result['required'][] = $key;
            }
        }

        return $result;
    }
}
