<?php

namespace Savks\Negotiator\Support\DTO;

use Savks\Negotiator\Exceptions\JitCompile;

use Savks\Negotiator\Support\Types\{
    ConstNumberType,
    NumberType
};

class ConstNumberValue extends ConstValue
{
    public function __construct(
        protected readonly int|float $value,
        protected readonly bool $asAnyNumber
    ) {
    }

    public function originalValue(): int|float
    {
        return $this->value;
    }

    protected function finalize(): int|float
    {
        return $this->value;
    }

    protected function types(): NumberType|ConstNumberType
    {
        return $this->asAnyNumber ? new NumberType() : new ConstNumberType($this->value);
    }

    protected function schema(): array
    {
        return [
            '$$type' => static::class,
            'value' => $this->value,
        ];
    }

    protected static function finalizeUsingSchema(array $schema, mixed $source, array $sourcesTrace = []): int|float
    {
        JitCompile::assertInvalidSchemaType($schema, static::class);

        return $schema['value'];
    }
}
