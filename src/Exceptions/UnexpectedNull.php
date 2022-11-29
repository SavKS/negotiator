<?php

namespace Savks\Negotiator\Exceptions;

use Closure;
use Savks\Negotiator\Support\DTO\Value;

class UnexpectedNull extends DTOException
{
    /**
     * @param class-string<Value> $sourceFQN
     */
    public function __construct(string $sourceFQN, string|Closure $accessor)
    {
        parent::__construct(
            sprintf(
                '"%s" expect not nullable value. Accessor: %s',
                $sourceFQN,
                $accessor ?
                    (\is_string($accessor) ? $accessor : 'CUSTOM_ACCESSOR') :
                    'NOT_EXISTS'
            )
        );
    }
}
