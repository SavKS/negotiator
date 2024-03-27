<?php

namespace Savks\Negotiator\Exceptions;

use Illuminate\Support\Arr;
use Throwable;

class InternalException extends DTOException
{
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

    public static function wrap(
        InternalException|MappingFail|Throwable $exception,
        string|int|array|null $path = null,
        bool $prepend = false
    ): static {
        if (static::needIgnore($exception)) {
            throw $exception;
        }

        if ($exception instanceof MappingFail) {
            $path = Arr::wrap($path);

            array_splice($path, 1, 0, '<' . $exception->mapper::class . '>');

            $exception = $exception->exception;
        }

        if ($exception instanceof InternalException) {
            $path = $prepend ?
                [
                    ...Arr::wrap($exception->path),
                    ...Arr::wrap($path),
                ] :
                [
                    ...Arr::wrap($path),
                    ...Arr::wrap($exception->path),
                ];

            $originalException = $exception->originalException;
        } else {
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
