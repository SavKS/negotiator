<?php

namespace Savks\Negotiator\Support\DTO;

use Closure;
use Illuminate\Support\Arr;
use Savks\Negotiator\Support\DTO\Utils\Factory;

use Savks\Negotiator\Exceptions\{
    DTOException,
    JitCompile
};
use Savks\Negotiator\Support\Types\{
    Type,
    Types
};

class UnionType extends NullableValue
{
    /**
     * @var list<array{
     *     'condition': bool|Closure(mixed): bool,
     *     'callback': Closure(Factory): Value
     * }>
     */
    protected array $variants = [];

    /**
     * @var Closure(Factory):Value|null
     */
    protected Closure|null $defaultVariant = null;

    public function __construct(
        protected readonly mixed $source,
        protected readonly string|Closure|null $accessor = null
    ) {
    }

    /**
     * @param Closure(Factory): Value $callback
     */
    public function variant(bool|Closure $condition, Closure $callback): static
    {
        $this->variants[] = [
            'condition' => $condition,
            'callback' => $callback,
        ];

        return $this;
    }

    /**
     * @param Closure(Factory): Value $callback
     */
    public function default(Closure $callback): static
    {
        $this->defaultVariant = $callback;

        return $this;
    }

    protected function finalize(): mixed
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

        foreach ($this->variants as $variant) {
            if (! $variant['condition']($value, ...$this->sourcesTrace)) {
                continue;
            }

            return $variant['callback'](
                new Factory($value, $this->sourcesTrace)
            )->finalize();
        }

        if ($this->defaultVariant) {
            return ($this->defaultVariant)(
                new Factory($value, $this->sourcesTrace)
            )->finalize();
        }

        $type = is_object($value) ? $value::class : gettype($value);

        if ($type === 'array') {
            $type = 'array<' . json_encode($value, JSON_UNESCAPED_UNICODE) . '>';
        } elseif ($type === 'object') {
            $type = 'object<' . $value::class . '>';
        }

        throw new DTOException("Unhandled union type variant for \"{$type}\"");
    }

    protected function types(): Type|Types
    {
        $types = [];

        foreach ($this->variants as $variant) {
            /** @var Value $value */
            $value = $variant['callback'](
                new Factory(null)
            );

            $types[] = $value->compileTypes()->types;
        }

        if ($this->defaultVariant) {
            $types[] = ($this->defaultVariant)(new Factory(null))->compileTypes()->types;
        }

        $types = Arr::flatten($types);

        return count($types) > 1 ? new Types($types) : head($types);
    }

    protected function schema(): array
    {
        $result = [
            '$$type' => static::class,
            'accessor' => $this->accessor,
            'variants' => [],
            'defaultVariantSchema' => null,
        ];

        foreach ($this->variants as $variant) {
            $result['variants'][] = [
                'condition' => $variant['condition'],
                'schema' => $variant['callback'](
                    new Factory(null)
                )->compileSchema(),
            ];
        }

        if ($this->defaultVariant) {
            $result['defaultVariantSchema'] = ($this->defaultVariant)(
                new Factory(null)
            )->compileSchema();
        }

        return $result;
    }

    protected static function finalizeUsingSchema(array $schema, mixed $source, array $sourcesTrace = []): mixed
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

        foreach ($schema['variants'] as $variant) {
            if (! $variant['condition']($value, ...$sourcesTrace)) {
                continue;
            }

            /** @var class-string<Value> $variantSchemaType */
            $variantSchemaType = $variant['schema']['$$type'];

            return $variantSchemaType::compileUsingSchema(
                $variant['schema'],
                $value,
                $sourcesTrace
            );
        }

        if ($schema['defaultVariant']) {
            /** @var class-string<Value> $defaultVariantSchemaType */
            $defaultVariantSchemaType = $schema['defaultVariant']['$$type'];

            return $defaultVariantSchemaType::compileUsingSchema(
                $value,
                $sourcesTrace
            );
        }

        $type = is_object($value) ? $value::class : gettype($value);

        if ($type === 'array') {
            $type = 'array<' . json_encode($value, JSON_UNESCAPED_UNICODE) . '>';
        } elseif ($type === 'object') {
            $type = 'object<' . $value::class . '>';
        }

        throw new DTOException("Unhandled union type variant for \"{$type}\"");
    }
}
