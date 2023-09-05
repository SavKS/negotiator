<?php

namespace Savks\Negotiator\Exceptions;

use Savks\Negotiator\Support\Mapping\Mapper;

class MappingFail extends DTOException
{
    public function __construct(Mapper $mapper, UnexpectedValue $e = null)
    {
        parent::__construct(
            sprintf(
                '[%s]%s',
                $mapper::class,
                $e ? " {$e->getMessage()}" : ''
            ),
            previous: $e
        );
    }
}
