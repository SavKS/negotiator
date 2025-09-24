<?php

namespace Savks\Negotiator\Mapping;

use Savks\Negotiator\Support\Mapping\Casts\Cast;
use Savks\Negotiator\Support\Mapping\Mapper;

class SchemasRepository
{
    /**
     * @var array<class-string<Mapper>, Cast>
     */
    protected array $schemas = [];

    /**
     * @param class-string<Mapper> $mapper
     */
    public function resolve(string $mapper): Cast
    {
        if (! isset($this->schemas[$mapper])) {
            $this->schemas[$mapper] = $mapper::schema();
        }

        return $this->schemas[$mapper];
    }
}
