<?php

namespace Savks\Negotiator\Support\Mapping;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\JsonResponse;
use JsonSerializable;
use Savks\Negotiator\Support\DTO\Value;

use Savks\Negotiator\Exceptions\{
    MappingFail,
    UnexpectedValue
};

/**
 * @method static customMock()
 */
abstract class Mapper implements JsonSerializable, Responsable
{
    abstract public function map(): Value|array|null;

    public static function defaultValues(): ?array
    {
        return null;
    }

    public function finalize(): mixed
    {
        try {
            return $this->map()->compile();
        } catch (UnexpectedValue $e) {
            throw new MappingFail($this, $e);
        }
    }

    public function jsonSerialize(): mixed
    {
        return $this->finalize();
    }

    public function toResponse($request): JsonResponse
    {
        return \response()->json(
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
