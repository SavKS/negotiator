<?php

namespace Savks\Negotiator\Support\DTO;

use Savks\Negotiator\Exceptions\JitCompile;

use Savks\Negotiator\Support\Types\{
    BooleanType,
    ConstBooleanType
};

/**
 * @extends ConstValue<bool>
 */
class ConstBooleanValue extends ConstValue
{
    public function __construct(
        protected readonly bool $value,
        protected readonly bool $asAnyBool
    ) {
    }

    public function originalValue(): bool
    {
        return $this->value;
    }

    protected function finalize(): bool
    {
        return $this->value;
    }

    protected function types(): BooleanType|ConstBooleanType
    {
        return $this->asAnyBool ? new BooleanType() : new ConstBooleanType($this->value);
    }

    protected function schema(): array
    {
        return [
            '$$type' => static::class,
            'value' => $this->value,
        ];
    }

    protected static function finalizeUsingSchema(array $schema, mixed $source, array $sourcesTrace = []): bool
    {
        JitCompile::assertInvalidSchemaType($schema, static::class);

        return $schema['value'];
    }
}
