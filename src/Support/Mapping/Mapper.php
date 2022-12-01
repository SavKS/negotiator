<?php

namespace Savks\Negotiator\Support\Mapping;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\JsonResponse;
use JsonSerializable;
use Savks\Negotiator\Support\DTO\Value;

abstract class Mapper implements JsonSerializable, Responsable
{
    abstract public function map(): Value|array|null;

    public static function defaultValues(): ?array
    {
        return null;
    }

    public function finalize(): mixed
    {
        return $this->map()->compile();
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
