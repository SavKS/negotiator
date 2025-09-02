<?php

namespace Savks\Negotiator\Support\TypeGeneration\TypeScript\RefResolver\Rules;

use BackedEnum;
use Closure;
use Savks\Negotiator\Support\Mapping\Mapper;
use Savks\Negotiator\Support\TypeGeneration\TypeScript\RefResolver\RefMatchResult;

/**
 * @template TSubject of BackedEnum|Mapper
 */
readonly class PredicateRule
{
    /**
     * @param Closure(class-string<TSubject> $subject):bool $predicate
     * @param Closure(class-string<TSubject> $subject):(RefMatchResult|null) $resolver
     */
    public function __construct(
        public Closure $predicate,
        public Closure $resolver
    ) {
    }

    /**
     * @param class-string<TSubject> $subject
     */
    public function resolve(string $subject): ?RefMatchResult
    {
        if (! ($this->predicate)($subject)) {
            return null;
        }

        return ($this->resolver)($subject);
    }
}
