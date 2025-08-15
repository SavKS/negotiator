<?php

namespace Savks\Negotiator\Exceptions;

use Illuminate\Support\Arr;
use Throwable;

class InternalException extends DTOException
{
    /**
     * @param string|int|list<string|int>|null $path
     */
    final protected function __construct(
        public readonly Throwable $originalException,
        public readonly string|int|array|null $path = null
    ) {
        parent::__construct(
            sprintf(
                'Internal exception in "%s". Message: %s',
                implode(
                    '.',
                    Arr::wrap($path ?? 'ROOT')
                ),
                $originalException->getMessage()
            ),
            previous: $originalException
        );
    }

    /**
     * @param string|int|list<string|int>|null $path
     *
     * @throws Throwable
     */
    public static function wrap(
        InternalException|MappingFail|Throwable $exception,
        string|int|array|null $path = null,
        bool $prepend = false
    ): static {
        if (static::needIgnore($exception)) {
            throw $exception;
        }

        if ($exception instanceof MappingFail) {
            /** @var list<string|int> $path */
            $path = Arr::wrap($path);

            array_splice($path, 1, 0, '<' . $exception->mapper::class . '>');

            $exception = $exception->exception;
        } elseif ($exception instanceof CastFail) {
            /** @var list<string|int> $path */
            $path = Arr::wrap($path);

            array_splice($path, 1, 0, "label({$exception->label})");

            $exception = $exception->exception;
        }

        if ($exception instanceof InternalException) {
            /** @var list<string|int> $path */
            $path = $prepend
                ? [
                    ...Arr::wrap($exception->path),
                    ...Arr::wrap($path),
                ]
                : [
                    ...Arr::wrap($path),
                    ...Arr::wrap($exception->path),
                ];

            $originalException = $exception->originalException;
        } else {
            /** @var list<string|int> $path */
            $path = Arr::wrap($path);

            $originalException = $exception;
        }

        return new static($originalException, $path);
    }

    protected static function needIgnore(Throwable $exception): bool
    {
        $ignore = config('negotiator.ignore_exceptions');

        if (! $ignore) {
            return false;
        }

        foreach ($ignore as $ignoreException) {
            if (is_a($exception, $ignoreException)) {
                return true;
            }
        }

        return false;
    }
}
