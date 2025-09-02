<?php

namespace Savks\Negotiator\Support\TypeGeneration\TypeScript\RefResolver\Rules;

use BackedEnum;
use Closure;
use Savks\Negotiator\Support\Mapping\Mapper;
use Savks\Negotiator\Support\TypeGeneration\TypeScript\RefResolver\RefMatchResult;

/**
 * @template TSubject of BackedEnum|Mapper
 */
final readonly class CallbackRule
{
    /**
     * @param Closure(class-string<TSubject> $subject):(RefMatchResult|null) $callback
     */
    public function __construct(protected Closure $callback)
    {
    }

    /**
     * @param class-string<TSubject> $subject
     */
    public function resolve(string $subject): ?RefMatchResult
    {
        return ($this->callback)($subject);
    }
}
