<?php

namespace Savks\Negotiator\Exceptions;

use Illuminate\Support\Arr;

class UnexpectedValue extends DTOException
{
    final public function __construct(
        public readonly string|array $types,
        public readonly mixed $value,
        public readonly string|array|null $path = null
    ) {
        parent::__construct(
            sprintf(
                'Invalid value expect "%s" in "%s", given "%s".',
                \implode(
                    '|',
                    Arr::wrap($types)
                ),
                \implode(
                    '.',
                    Arr::wrap($path ?? 'ROOT')
                ),
                \gettype($value)
            )
        );
    }

    /**
     * @param string|string[] $path
     */
    public static function wrap(UnexpectedValue $e, string|array $path): static
    {
        return new static($e->types, $e->value, [
            ...Arr::wrap($path),
            ...Arr::wrap($e->path),
        ]);
    }
}
