<?php

namespace Savks\Negotiator\Exceptions;

use Savks\Negotiator\Support\Mapping\Mapper;

class MappingFail extends DTOException
{
    public function __construct(
        public readonly Mapper $mapper,
        public readonly UnexpectedValue|InternalException|string|null $exception = null
    ) {
        parent::__construct(
            sprintf(
                '[%s]%s',
                $mapper::class,
                is_string($exception)
                    ? $this->exception
                    : ($exception ? " {$exception->getMessage()}" : '')

            ),
            previous: ! is_string($exception) ? $exception : null
        );
    }
}
