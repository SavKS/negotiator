<?php

namespace Savks\Negotiator {

    use Closure;

    /**
     * @param array<mixed, mixed> $sourcesTrace
     */
    function resolve_value_from_accessor(
        string|Closure|null $accessor,
        array $sourcesTrace
    ): mixed {
        return match (true) {
            $accessor === null => $sourcesTrace[0] ?? null,

            is_string($accessor) => data_get($sourcesTrace[0] ?? null, $accessor),

            default => $accessor(...$sourcesTrace)
        };
    }
}
