<?php

namespace Savks\Negotiator\TypeGeneration;

use ReflectionClass;
use ReflectionParameter;
use RuntimeException;
use Savks\Negotiator\Support\Mapping\Mapper;

use Savks\Negotiator\TypeGeneration\Mock\{
    EmptyIntEnum,
    EmptyStringEnum
};

class Faker
{
    public function makeMapper(string $mapperFQN): Mapper
    {
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

    protected function mockParameter(ReflectionParameter $parameter): mixed
    {
        $type = $parameter->getType();

        if ($type->allowsNull()) {
            return null;
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
