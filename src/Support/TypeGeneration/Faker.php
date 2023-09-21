<?php

namespace Savks\Negotiator\Support\TypeGeneration;

use BackedEnum;
use ReflectionClass;
use ReflectionIntersectionType;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionUnionType;
use Savks\Negotiator\Exceptions\MockFail;
use Savks\Negotiator\Support\TypeGeneration\Types\AliasType;
use UnitEnum;

use Savks\Negotiator\Support\Mapping\{
    Casts\Cast,
    Casts\NullableCast,
    Mapper,
    WithCustomMock
};

class Faker
{
    public function makeMapper(string $mapperFQN): Mapper
    {
        $alias = app(MapperAliases::class)->resolve($mapperFQN);

        if ($alias) {
            return $this->makeAliasMapper($alias);
        }

        if (class_implements($mapperFQN)[WithCustomMock::class] ?? null) {
            return $mapperFQN::mock();
        }

        $mapperRef = new ReflectionClass($mapperFQN);

        $constructorRef = $mapperRef->getConstructor();

        if (! $constructorRef) {
            return new $mapperFQN();
        }

        return new $mapperFQN(
            ...array_map(
                fn (ReflectionParameter $parameter) => $this->mockParameter($parameter),
                $constructorRef->getParameters()
            )
        );
    }

    protected function makeAliasMapper(string $alias): Mapper
    {
        $mapper = new class() extends Mapper {
            public static string $alias;

            public static function schema(): Cast
            {
                return new class (static::$alias) extends NullableCast {
                    public function __construct(public readonly string $alias)
                    {
                    }

                    protected function finalize(mixed $source, array $sourcesTrace): mixed
                    {
                        return null;
                    }

                    protected function types(): AliasType
                    {
                        return new AliasType($this->alias);
                    }
                };
            }
        };

        $mapper::$alias = $alias;

        return $mapper;
    }

    public function mockParameter(ReflectionParameter $parameter): mixed
    {
        /** @var ReflectionIntersectionType|ReflectionNamedType|ReflectionUnionType|null $type */
        $type = $parameter->getType();

        if ($type->allowsNull()) {
            return null;
        }

        if ($type === null) {
            throw new MockFail('Type is null.');
        }

        if ($type instanceof ReflectionIntersectionType) {
            throw new MockFail('Intersected types are not supported.');
        } elseif ($type instanceof ReflectionUnionType) {
            $unionTriedTypes = [];

            foreach ($type->getTypes() as $unionType) {
                try {
                    return $this->resolveValueForType($unionType);
                } catch (MockFail) {
                    $unionTriedTypes[] = $unionType->getName();
                }
            }

            throw new MockFail(
                sprintf(
                    'Any type "%s" from union are not supported.',
                    implode(', ', $unionTriedTypes)
                )
            );
        }

        return $this->resolveValueForType($type);
    }

    protected function resolveValueForType(ReflectionNamedType $type)
    {
        switch ($type->getName()) {
            case 'int':
                return 0;

            case 'float':
                return 0.0;

            case 'string':
                return '';

            case 'bool':
                return false;

            case 'array':
                return [];
        }

        if ($type->getName() === 'Closure') {
            return fn () => null;
        } elseif (is_subclass_of($type->getName(), BackedEnum::class)) {
            /** @var class-string<UnitEnum> $enumFQN */
            $enumFQN = $type->getName();

            return head(
                $enumFQN::cases()
            );
        } elseif (class_exists($type->getName())) {
            $ref = new ReflectionClass(
                $type->getName()
            );

            return $ref->newInstanceWithoutConstructor();
        }

        throw new MockFail("Invalid type \"{$type->getName()}\"");
    }
}