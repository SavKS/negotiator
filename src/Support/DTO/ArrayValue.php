<?php

namespace Savks\Negotiator\Support\DTO;

use Closure;
use Savks\Negotiator\Support\DTO\ArrayValue\Item;

use Savks\Negotiator\Exceptions\{
    JitCompile,
    UnexpectedValue
};
use Savks\Negotiator\Support\Types\{
    ArrayType,
    Type,
    Types
};

class ArrayValue extends NullableValue
{
    /**
     * @var (Closure(mixed): bool)|null
     */
    protected ?Closure $filter = null;

    public function __construct(
        protected readonly mixed $source,
        protected readonly string|Closure $iterator,
        protected readonly string|Closure|null $accessor = null
    ) {
    }

    /**
     * @param Closure(mixed): bool $closure
     */
    public function filter(Closure $closure): static
    {
        $this->filter = $closure;

        return $this;
    }

    protected function finalize(): ?array
    {
        $value = $this->resolveValueFromAccessor(
            $this->accessor,
            $this->source,
            $this->sourcesTrace
        );

        if ($this->accessor && last($this->sourcesTrace) !== $this->source) {
            $this->sourcesTrace[] = $this->source;
        }

        if ($value === null) {
            return null;
        }

        if (! is_iterable($value)) {
            throw new UnexpectedValue('iterable', $value);
        }

        $result = [];

        $value = is_array($value) ? $value : iterator_to_array($value);

        foreach (array_values($value) as $index => $item) {
            $listItemValue = ($this->iterator)(
                new Item($index, $item, $this->sourcesTrace)
            );

            if (! $listItemValue instanceof Value) {
                throw new UnexpectedValue(Value::class, $listItemValue, $index);
            }

            try {
                $data = $listItemValue->compile();

                if ($this->filter && ! ($this->filter)($data)) {
                    continue;
                }

                $result[] = $data;
            } catch (UnexpectedValue $e) {
                throw UnexpectedValue::wrap($e, $index);
            }
        }

        return $result;
    }

    protected function types(): Type|Types
    {
        /** @var Value $value */
        $value = ($this->iterator)(
            new Item(0, null)
        );

        return new ArrayType(
            $value->compileTypes()
        );
    }

    protected function schema(): array
    {
        $listItemValue = ($this->iterator)(
            new Item(0, null, [])
        );

        if (! $listItemValue instanceof Value) {
            throw new UnexpectedValue(Value::class, $listItemValue, 0);
        }

        return [
            '$$type' => static::class,
            'accessor' => $this->accessor,
            'itemSchema' => $listItemValue->compileSchema(),
            'filter' => $this->filter,
        ];
    }

    protected static function finalizeUsingSchema(array $schema, mixed $source, array $sourcesTrace = []): ?array
    {
        JitCompile::assertInvalidSchemaType($schema, static::class);

        $value = static::resolveValueFromAccessor(
            $schema['accessor'],
            $source,
            $sourcesTrace
        );

        if ($schema['accessor'] && last($sourcesTrace) !== $source) {
            $sourcesTrace[] = $source;
        }

        if ($value === null) {
            return null;
        }

        if (! is_iterable($value)) {
            throw new UnexpectedValue('iterable', $value);
        }

        $result = [];

        $value = is_array($value) ? $value : iterator_to_array($value);

        $itemSchema = $schema['itemSchema'];

        /** @var class-string<Value> $itemSchemaType */
        $itemSchemaType = $itemSchema['$$type'];

        foreach (array_values($value) as $index => $item) {
            try {
                $data = $itemSchemaType::compileUsingSchema(
                    $itemSchema,
                    $item,
                    $sourcesTrace
                );

                if ($schema['filter'] && ! ($schema['filter'])($data)) {
                    continue;
                }

                $result[] = $data;
            } catch (UnexpectedValue $e) {
                throw UnexpectedValue::wrap($e, $index);
            }
        }

        return $result;
    }
}
