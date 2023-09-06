<?php

namespace Savks\Negotiator\Support\DTO\Utils;

use Closure;
use Savks\Negotiator\Support\DTO\Value;
use Savks\Negotiator\Support\Types\Types;

class Intersection extends Value
{
    /**
     * @param Closure(Factory):list<Value> $callback
     */
    public function __construct(
        protected readonly mixed $source,
        protected readonly Closure $callback,
        protected readonly string|Closure|null $accessor = null
    ) {
    }

    protected function finalize(): array
    {
        $value = static::resolveValueFromAccessor(
            $this->accessor,
            $this->source,
            $this->sourcesTrace
        );

        if ($this->accessor && last($this->sourcesTrace) !== $this->source) {
            $this->sourcesTrace[] = $this->source;
        }

        $result = [];

        $mappedObjects = ($this->callback)(
            new Factory($value, $this->sourcesTrace)
        );

        foreach ($mappedObjects as $mappedObject) {
            $objectResult = $mappedObject->compile();

            if ($objectResult !== null) {
                $result[] = $objectResult;
            }
        }

        return array_merge(...$result);
    }

    protected function types(): Types
    {
        $types = [];

        $objects = ($this->callback)(
            new Factory(null)
        );

        foreach ($objects as $object) {
            $types[] = $object->compileTypes()->types;
        }

        return new Types(
            array_merge(...$types),
            true
        );
    }

    protected function schema(): array
    {
        $result = [
            '$$type' => static::class,
            'accessor' => $this->accessor,
            'schemas' => [],
        ];

        $objects = ($this->callback)(
            new Factory(null)
        );

        foreach ($objects as $object) {
            $objectSchema = $object->compileSchema();

            if ($objectSchema !== null) {
                $result['schemas'][] = $objectSchema;
            }
        }

        return $result;
    }

    protected static function finalizeUsingSchema(array $schema, mixed $source, array $sourcesTrace = []): mixed
    {
        $value = static::resolveValueFromAccessor(
            $schema['accessor'],
            $source,
            $sourcesTrace
        );

        if ($schema['accessor'] && last($sourcesTrace) !== $source) {
            $sourcesTrace[] = $source;
        }

        $result = [];

        foreach ($schema['schemas'] as $objectSchema) {
            /** @var class-string<Value> $objectSchemaType */
            $objectSchemaType = $objectSchema['$$type'];

            $objectResult = $objectSchemaType::compileUsingSchema(
                $objectSchema,
                $value,
                $sourcesTrace
            );

            if ($objectResult !== null) {
                $result[] = $objectResult;
            }
        }

        return array_merge(...$result);
    }
}
