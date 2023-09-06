<?php

namespace Savks\Negotiator\Support\DTO;

use Savks\Negotiator\Exceptions\JitCompile;
use Savks\Negotiator\Support\Types\NullType;

class NullValue extends NullableValue
{
    public bool $nullable = true;

    public function __construct()
    {
    }

    protected function finalize(): mixed
    {
        return null;
    }

    protected function types(): NullType
    {
        return new NullType();
    }

    protected function schema(): array
    {
        return [
            '$$type' => static::class,
        ];
    }

    protected static function finalizeUsingSchema(array $schema, mixed $source, array $sourcesTrace = []): mixed
    {
        JitCompile::assertInvalidSchemaType($schema, static::class);

        return null;
    }
}
