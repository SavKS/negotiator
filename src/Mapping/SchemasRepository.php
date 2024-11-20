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
     * @param class-string<Mapper> $mapperFQN
     */
    public function resolve(string $mapperFQN): Cast
    {
        if (! isset($this->schemas[$mapperFQN])) {
            $this->schemas[$mapperFQN] = $mapperFQN::schema();
        }

        return $this->schemas[$mapperFQN];
    }
}
