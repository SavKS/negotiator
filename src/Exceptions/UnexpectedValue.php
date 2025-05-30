<?php

namespace Savks\Negotiator\Exceptions;

use BackedEnum;
use Illuminate\Support\Arr;

class UnexpectedValue extends DTOException
{
    /**
     * @param string[]|string $types
     * @param string|int|list<string|int>|null $path
     */
    final public function __construct(
        public readonly string|array $types,
        public readonly mixed $value,
        public readonly string|int|array|null $path = null
    ) {
        if (! is_string($this->value) && $this->value instanceof BackedEnum) {
            $normalizedValueType = $this->value::class . '::' . $this->value->name;
        } elseif (is_object($this->value)) {
            $normalizedValueType = 'object<' . get_class($this->value) . '>';
        } else {
            $normalizedValueType = gettype($value);

            if (is_scalar($this->value)) {
                $normalizedValueType = "{$normalizedValueType}<{$this->value}>";
            }
        }

        parent::__construct(
            sprintf(
                'Invalid value expect "%s" in "%s", given "%s".',
                implode(
                    '|',
                    Arr::wrap($types)
                ),
                implode(
                    '.',
                    Arr::wrap($path ?? 'ROOT')
                ),
                $normalizedValueType
            )
        );
    }

    /**
     * @param string|int|list<string|int> $path
     */
    public static function wrap(UnexpectedValue $e, string|int|array $path, bool $prepend = false): static
    {
        if ($prepend) {
            /** @var list<string|int> $resultPath */
            $resultPath = [
                ...Arr::wrap($e->path),
                ...Arr::wrap($path),
            ];
        } else {
            /** @var list<string|int> $resultPath */
            $resultPath = [
                ...Arr::wrap($path),
                ...Arr::wrap($e->path),
            ];
        }

        return new static($e->types, $e->value, $resultPath);
    }
}
