<?php

namespace Savks\Negotiator\Exceptions;

use Closure;
use Savks\Negotiator\Support\DTO\AnyValue;

class UnexpectedNull extends DTOException
{
    /**
     * @param class-string<AnyValue> $sourceFQN
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
