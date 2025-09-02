<?php

namespace Savks\Negotiator\Support\TypeGeneration\TypeScript\RefResolver\Rules;

use BackedEnum;
use Closure;
use Savks\Negotiator\Support\Mapping\Mapper;
use Savks\Negotiator\Support\TypeGeneration\TypeScript\RefResolver\RefMatchResult;

/**
 * @template TSubject of BackedEnum|Mapper
 */
readonly class RegexRule
{
    /**
     * @param Closure(array<array-key, string> $matches, class-string<TSubject> $subject):(RefMatchResult|null) $resolver
     */
    public function __construct(
        public string $pattern,
        public Closure $resolver
    ) {
    }

    /**
     * @param class-string<TSubject> $subject
     */
    public function resolve(string $subject): ?RefMatchResult
    {
        preg_match($this->pattern, $subject, $matches);

        if (! $matches) {
            return null;
        }

        return ($this->resolver)($matches, $subject);
    }
}
