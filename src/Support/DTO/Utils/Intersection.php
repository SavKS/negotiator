<?php

namespace Savks\Negotiator\Support\DTO\Utils;

use Savks\Negotiator\Contexts\ObjectIgnoredKeysContext;
use Savks\Negotiator\Exceptions\UnexpectedValue;
use Savks\Negotiator\Support\Mapping\Mapper;
use Savks\Negotiator\TypeGeneration\MapperAliases;
use Savks\PhpContexts\Context;

use Savks\Negotiator\Support\DTO\{
    AnyObjectValue,
    KeyedArrayValue,
    ObjectValue,
    Value
};
use Savks\Negotiator\Support\Types\{
    AliasType,
    Types
};

class Intersection extends Value
{
    public readonly array $objects;

    public function __construct(Value|Mapper ...$objects)
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
                $normalizedObject = $object instanceof Mapper ? $object->map() : $object;

                if ($normalizedObject instanceof ObjectValue
                    || $normalizedObject instanceof AnyObjectValue
                    || $normalizedObject instanceof KeyedArrayValue
                ) {
                    $objectResult = $normalizedObject->compile();

                    if ($objectResult !== null) {
                        $context->push(
                            \array_keys($objectResult)
                        );

                        $result[] = $objectResult;
                    }
                } else {
                    throw new UnexpectedValue(
                        [ObjectValue::class, AnyObjectValue::class, KeyedArrayValue::class],
                        $object,
                        \sprintf(
                            'Intersection position %s',
                            $index,
                            $object instanceof Mapper ? ' - Mapper<' . $object::class . '>' : ''
                        )
                    );
                }

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
