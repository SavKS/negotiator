<?php

namespace Savks\Negotiator\Exceptions;

use Closure;
use ReflectionFunction;
use Savks\Negotiator\Support\DTO\Value;
use Savks\Negotiator\Support\Mapping\Mapper;

class UnexpectedNull extends DTOException
{
    /**
     * @param class-string<Value> $sourceFQN
     */
    public function __construct(string $sourceFQN, string|Closure $accessor)
    {
        $accessorInfo = null;

        if (\is_string($accessor)) {
            $accessorInfo = $accessor;
        } elseif ($accessor instanceof Closure) {
            $reflection = new ReflectionFunction($accessor);

            $accessorInfo = \sprintf(
                '%s:%s',
                $reflection->getFileName(),
                $reflection->getStartLine()
            );
        }

        $mappersTrace = \implode(
            ' -> ',
            \array_map(
                fn (array $mapper) => \get_class($mapper['object']),
                \array_filter(
                    \debug_backtrace(),
                    fn (array $item) => ($item['object'] ?? null) instanceof Mapper
                )
            )
        );

        parent::__construct(
            sprintf(
                '%s"%s" expect not nullable value. Accessor: %s',
                $mappersTrace ? "[{$mappersTrace}] " : '',
                $sourceFQN,
                $accessorInfo ?? 'NOT_EXISTS'
            )
        );
    }
}
