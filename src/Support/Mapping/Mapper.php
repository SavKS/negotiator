<?php

namespace Savks\Negotiator\Support\Mapping;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\JsonResponse;
use JsonSerializable;
use Savks\Negotiator\Enums\PerformanceTrackers;
use Savks\Negotiator\Mapping\SchemasRepository;
use Savks\Negotiator\Performance\Performance;
use Savks\Negotiator\Support\Mapping\Casts\Cast;

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

    abstract public static function schema(): Cast;

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

        $schema = app(SchemasRepository::class)->resolve(static::class);

        $performance = app(Performance::class);

        try {
            if ($performance->trackedEnabled(PerformanceTrackers::MAPPERS)) {
                $event = $performance->event("Mapper: {$className}", [
                    'class_fqn' => static::class,
                ]);

                $event->begin();

                $result = $schema->resolve($this, []);

                $event->end();

                return $result;
            }

            return $schema->resolve($this, []);
        } catch (UnexpectedValue $e) {
            throw new MappingFail($this, $e);
        }
    }

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
