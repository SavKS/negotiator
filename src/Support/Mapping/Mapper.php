<?php

namespace Savks\Negotiator\Support\Mapping;

use Closure;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\JsonResponse;
use JsonSerializable;
use Savks\Negotiator\Enums\PerformanceTrackers;
use Savks\Negotiator\Jit\Jit;
use Savks\Negotiator\Performance\Performance;

use Savks\Negotiator\Exceptions\{
    MappingFail,
    UnexpectedValue
};
use Savks\Negotiator\Support\DTO\{
    Utils\Factory,
    Utils\Intersection,
    Value
};

/**
 * @method static customMock()
 */
abstract class Mapper implements JsonSerializable, Responsable
{
    protected bool $enableJit = false;

    /**
     * @var Generic[]|null
     */
    protected ?array $generics = null;

    abstract public function map(): Value|Mapper|Intersection|Jit;

    /**
     * @return GenericDeclaration[]
     */
    public function declareGenerics(): array
    {
        return [];
    }

    /**
     * @param Closure(Factory):Value $callback
     */
    public function factory(Closure $callback): Value
    {
        return $this->enableJit ?
            new Jit($this, $callback) :
            $callback(new Factory($this));
    }

    public function finalize(): mixed
    {
        $className = class_basename(static::class);

        $performance = app(Performance::class);

        try {
            if ($performance->trackedEnabled(PerformanceTrackers::MAPPERS)) {
                $event = $performance->event("Mapper: {$className}", [
                    'class_fqn' => static::class,
                ]);

                $event->begin();

                $result = $this->map()->compile();

                $event->end();

                return $result;
            }

            return $this->map()->compile();
        } catch (UnexpectedValue $e) {
            throw new MappingFail($this, $e);
        }
    }

    public function dd(): never
    {
        dd(
            $this->finalize()
        );
    }

    public function jsonSerialize(): mixed
    {
        return $this->finalize();
    }

    public function toResponse($request): JsonResponse
    {
        return response()->json(
            $this->jsonSerialize(),
            $this->httpStatus(),
            options: $this->jsonOptions(),
        );
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
