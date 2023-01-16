<?php

namespace Savks\Negotiator\Support\DTO\Utils;

use Closure;
use ReflectionFunction;
use Savks\Negotiator\Contexts\ObjectIgnoredKeysContext;
use Savks\Negotiator\Exceptions\UnexpectedValue;
use Savks\Negotiator\Support\Mapping\Mapper;
use Savks\PhpContexts\Context;

use Savks\Negotiator\Support\DTO\{
    ObjectValue,
    Value
};
use Savks\Negotiator\Support\Types\{
    AliasType,
    AnyType,
    Type,
    Types
};
use Savks\Negotiator\TypeGeneration\{
    Faker,
    MapperAliases
};

class Intersection extends Value
{
    public readonly array $objects;

    /**
     * @param list<ObjectValue|Mapper|mixed> $objects
     */
    public function __construct(ObjectValue|Mapper ...$objects)
    {
        $this->objects = $objects;
    }

    protected function finalize(): array
    {
        return (new ObjectIgnoredKeysContext())->wrap(function () {
            /** @var ObjectIgnoredKeysContext $context */
            $context = Context::use(ObjectIgnoredKeysContext::class);

            $result = [];

            $index = 0;

            foreach (\array_reverse($this->objects) as $object) {
                if ($object instanceof ObjectValue) {
                    $objectResult = $object->compile();
                } else {
                    if (! $object->map() instanceof ObjectValue) {
                        throw new UnexpectedValue(
                            ObjectValue::class,
                            $object,
                            \sprintf(
                                'Intersection position %s - Mapper<%s>',
                                $index,
                                $object::class
                            )
                        );
                    }

                    $objectResult = $object->finalize();
                }

                $context->push(
                    \array_keys($objectResult)
                );

                $result[] = $objectResult;

                $index++;
            }

            return \array_merge(
                ...\array_reverse($result)
            );
        });
    }

    protected function types(): Types
    {
        $types = [];

        foreach ($this->objects as $object) {
            if ($object instanceof Mapper) {
                $alias = \app(MapperAliases::class)->resolve($object::class);

                $types[] = $alias ?
                    [new AliasType($alias)] :
                    $object->map()->compileTypes()->types;
            } else {
                $types[] = $object->compileTypes()->types;
            }
        }

        return new Types(
            \array_merge(...$types),
            true
        );
    }
}
