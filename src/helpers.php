<?php

namespace Savks\Negotiator {

    use Closure;
    use Savks\Negotiator\Enums\PerformanceTrackers;
    use Savks\Negotiator\Exceptions\CastFail;
    use Savks\Negotiator\Exceptions\InternalException;
    use Savks\Negotiator\Exceptions\UnexpectedValue;
    use Savks\Negotiator\Performance\Performance;
    use Savks\Negotiator\Support\Mapping\Casts\Cast;

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

    /**
     * @throws CastFail
     */
    function cast(string $label, mixed $value, Cast $schema): mixed
    {
        $performance = app(Performance::class);

        try {
            if ($performance->trackedEnabled(PerformanceTrackers::CASTS)) {
                $event = $performance->event("Cast: {$label}", [
                    'label' => $label,
                ]);

                $event->begin();

                $result = $schema->resolve($value);

                $event->end();

                return $result;
            }

            return $schema->resolve($value);
        } catch (UnexpectedValue|InternalException $e) {
            throw new CastFail($label, $e);
        }
    }
}
