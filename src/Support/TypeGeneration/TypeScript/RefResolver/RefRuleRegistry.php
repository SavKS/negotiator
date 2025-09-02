<?php

namespace Savks\Negotiator\Support\TypeGeneration\TypeScript\RefResolver;

use BackedEnum;
use Closure;
use Savks\Negotiator\Support\Mapping\Mapper;
use Savks\Negotiator\Support\TypeGeneration\TypeScript\RefResolver\Rules\CallbackRule;
use Savks\Negotiator\Support\TypeGeneration\TypeScript\RefResolver\Rules\PredicateRule;
use Savks\Negotiator\Support\TypeGeneration\TypeScript\RefResolver\Rules\RegexRule;

/**
 * @template TSubject of BackedEnum|Mapper
 */
class RefRuleRegistry
{
    /**
     * @var array<CallbackRule<TSubject>|PredicateRule<TSubject>|RegexRule<TSubject>>
     */
    public protected(set) array $rules = [];

    /**
     * @param Closure(array<array-key, string> $matches, class-string<TSubject> $subject):(RefMatchResult|null) $resolver
     *
     * @return $this
     */
    public function registerRegexRule(string $pattern, Closure $resolver): static
    {
        $this->rules[] = new RegexRule($pattern, $resolver);

        return $this;
    }

    /**
     * @param Closure(class-string<TSubject> $subject):bool $predicate
     * @param Closure(class-string<TSubject> $subject):(RefMatchResult|null) $resolver
     */
    public function registerPredicateRule(Closure $predicate, Closure $resolver): static
    {
        $this->rules[] = new PredicateRule($predicate, $resolver);

        return $this;
    }

    /**
     * @param Closure(class-string<TSubject> $subject):(RefMatchResult|null) $callback
     *
     * @return $this
     */
    public function registerCallbackRule(Closure $callback): static
    {
        $this->rules[] = new CallbackRule($callback);

        return $this;
    }
}
