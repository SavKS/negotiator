<?php

namespace Savks\Negotiator\Exceptions;

class JitCompile extends JitFail
{
    public static function assertInvalidSchemaType(array $schema, string $expected): void
    {
        if ($schema['$$type'] !== $expected) {
            throw new self(
                sprintf(
                    'Invalid schema node type. Expected "%s" given "%s".',
                    $schema['$$type'],
                    $expected
                )
            );
        }
    }
}
