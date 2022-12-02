<?php

namespace Savks\Negotiator\Commands;

use ControlPackages\FileManager\Http\Mapping\FileMapper;
use Illuminate\Console\Command;
use ReflectionClass;
use ReflectionParameter;
use RuntimeException;

class GenerateTypes extends Command
{
    protected $signature = 'negotiator:generate:types';

    public function handle()
    {
        $mapper = $this->mockMapper(FileMapper::class);

        $value = $mapper->map();

        dd($value);
    }

    protected function mockMapper(string $mapperFQN)
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
        }

        throw new RuntimeException("Invalid type \"{$type->getName()}\"");
    }
}
