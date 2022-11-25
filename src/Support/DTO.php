<?php

namespace Savks\Negotiator\Support;

use Closure;
use Savks\Negotiator\Support\DTO\ObjectValue;

/**
 * @template T
 */
class DTO
{
    /**
     * @var array
     */
    protected array $value;

    /**
     * @param T $source
     */
    final protected function __construct(public readonly mixed $source)
    {
    }

    /**
     * @param Closure(ObjectValue): static $callback
     */
    public function to(Closure $callback): static
    {
        $this->value = new ObjectValue($this->source, $callback);

        return $this;
    }

    /**
     * @param T $source
     * @return DTO<T>
     */
    public static function from(mixed $source): self
    {
        return new self($source);
    }
}
