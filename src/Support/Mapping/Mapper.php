<?php

namespace Savks\Negotiator\Support\Mapping;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\JsonResponse;
use JsonSerializable;
use Savks\Negotiator\Enums\PerformanceTrackers;
use Savks\Negotiator\Performance\Performance;
use Savks\Negotiator\Support\DTO\Cast;

use Savks\Negotiator\Exceptions\{
    MappingFail,
    UnexpectedValue
};

/**
 * @method static customMock()
 */
abstract class Mapper implements JsonSerializable, Responsable
{
    /**
     * @var Generic[]|null
     */
    protected ?array $generics = null;

    /**
     * @return GenericDeclaration[]
     */
    public function declareGenerics(): array
    {
        return [];
    }

    public function dd(): never
    {
        dd(
            $this->resolve()
        );
    }

    public function resolve(): mixed
    {
        $className = class_basename(static::class);

        $performance = app(Performance::class);

        try {
            if ($performance->trackedEnabled(PerformanceTrackers::MAPPERS)) {
                $event = $performance->event("Mapper: {$className}", [
                    'class_fqn' => static::class,
                ]);

                $event->begin();

                $result = $this->schema()->resolve($this, []);

                $event->end();

                return $result;
            }

            return $this->schema()->resolve($this, []);
        } catch (UnexpectedValue $e) {
            throw new MappingFail($this, $e);
        }
    }

    abstract public function schema(): Cast;

    public function toResponse($request): JsonResponse
    {
        return response()->json(
            $this->jsonSerialize(),
            $this->httpStatus(),
            options: $this->jsonOptions(),
        );
    }

    public function jsonSerialize(): mixed
    {
        return $this->resolve();
    }

    protected function httpStatus(): int
    {
        return 200;
    }

    protected function jsonOptions(): int
    {
        return 0;
    }
}
