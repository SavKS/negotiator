<?php

namespace Savks\Negotiator\TypeGeneration;

use ReflectionClass;
use ReflectionIntersectionType;
use ReflectionParameter;
use RuntimeException;

use Savks\Negotiator\Support\Mapping\{
    Mapper,
    WithCustomMock
};
use Savks\Negotiator\TypeGeneration\Mock\{
    EmptyIntEnum,
    EmptyStringEnum
};

class Faker
{
    public function makeMapper(string $mapperFQN): Mapper
    {
        $alias = \app(MapperAliases::class)->resolve($mapperFQN);

        if ($alias) {
            return new AliasMapper($alias);
        }

        if (\class_implements($mapperFQN)[WithCustomMock::class] ?? null) {
            return $mapperFQN::mock();
        }

        $mapperRef = new ReflectionClass($mapperFQN);

        $constructorRef = $mapperRef->getConstructor();

        if (! $constructorRef) {
            return new $mapperFQN();
        }

        return new $mapperFQN(
            ...\array_map(
                fn (ReflectionParameter $parameter) => $this->mockParameter($parameter),
                $constructorRef->getParameters()
            )
        );
    }

    public function mockParameter(ReflectionParameter $parameter): mixed
    {
        $type = $parameter->getType();

        if ($type->allowsNull()) {
            return null;
        }

        if ($type instanceof ReflectionIntersectionType) {
            throw new RuntimeException('Intersected types are not supported.');
        }

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

        if (\class_exists($type->getName())) {
            $ref = new ReflectionClass(
                $type->getName()
            );

            return $ref->newInstanceWithoutConstructor();
        } elseif ($type->getName() === \BackedEnum::class
            || $type->getName() === \StringBackedEnum::class
        ) {
            return EmptyStringEnum::TEST;
        } elseif ($type->getName() === \IntBackedEnum::class) {
            return EmptyIntEnum::TEST;
        }

        throw new RuntimeException("Invalid type \"{$type->getName()}\"");
    }
}
