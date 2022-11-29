<?php

namespace Savks\Negotiator\Exceptions;

use Closure;
use Illuminate\Support\Arr;
use Savks\Negotiator\Support\DTO\Value;

class UnexpectedValue extends DTOException
{
    /**
     * @param class-string<Value> $sourceFQN
     */
    public function __construct(string $sourceFQN, string|array $types, mixed $value, string|Closure $accessor = null)
    {
        parent::__construct(
            sprintf(
                '"%s" expect "%s", given "%s". Accessor: "%s"',
                $sourceFQN,
                \implode(
                    '|',
                    Arr::wrap($types)
                ),
                \gettype($value),
                $accessor ?
                    (\is_string($accessor) ? $accessor : 'CUSTOM_ACCESSOR') :
                    'NOT_EXISTS'
            )
        );
    }
}
