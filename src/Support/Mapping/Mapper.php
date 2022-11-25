<?php

namespace Savks\Negotiator\Support\Mapping;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\JsonResponse;
use JsonSerializable;
use Savks\Negotiator\Support\DTO\AnyValue;

abstract class Mapper implements JsonSerializable, Responsable
{
    abstract public function map(): AnyValue|array|null;

    public static function defaultValues(): ?array
    {
        return null;
    }

    public function jsonSerialize(): mixed
    {
        return $this->map()->compile();
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
