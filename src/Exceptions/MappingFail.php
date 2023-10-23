<?php

namespace Savks\Negotiator\Exceptions;

use Savks\Negotiator\Support\Mapping\Mapper;

class MappingFail extends DTOException
{
    public function __construct(
        public readonly Mapper $mapper,
        public readonly UnexpectedValue|InternalException|null $exception = null
    ) {
        parent::__construct(
            sprintf(
                '[%s]%s',
                $mapper::class,
                $exception ? " {$exception->getMessage()}" : ''
            ),
            previous: $exception
        );
    }
}
