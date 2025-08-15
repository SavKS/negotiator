<?php

namespace Savks\Negotiator\Exceptions;

class CastFail extends DTOException
{
    public function __construct(
        public readonly string $label,
        public readonly UnexpectedValue|InternalException|null $exception = null
    ) {
        parent::__construct(
            sprintf(
                '[%s]%s',
                $label,
                $exception ? " {$exception->getMessage()}" : ''

            ),
            previous: $exception
        );
    }
}
