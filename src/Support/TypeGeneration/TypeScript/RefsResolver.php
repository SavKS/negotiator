<?php

namespace Savks\Negotiator\Support\TypeGeneration\TypeScript;

use BackedEnum;
use Closure;
use Illuminate\Support\Str;
use LogicException;
use Savks\Negotiator\Enums\RefTypes;
use Savks\Negotiator\Support\Mapping\Mapper;
use Savks\Negotiator\Support\TypeGeneration\TypeScript\RefResolver\RefRuleRegistry;

/**
 * @phpstan-type RawMatchResult array{string|string[],string}
 * @phpstan-type RawMapperVariantRule string|(Closure(class-string<Mapper> $mapperFQN):bool)
 * @phpstan-type RawMapperVariantResolver Closure(string[]|null $matches, class-string<Mapper> $mapperFQN):(RawMatchResult|null)
 * @phpstan-type RawMapperVariantMatch Closure(class-string<Mapper>):(RawMatchResult|null)
 * @phpstan-type RawMapperVariantConfig array{
 *     rule: RawMapperVariantRule,
 *     resolver: RawMapperVariantResolver,
 * }
 * @phpstan-type RawEnumVariantRule string|(Closure(class-string<BackedEnum> $enumFQN):bool)
 * @phpstan-type RawEnumVariantResolver Closure(string[]|null $matches, class-string<BackedEnum> $enumFQN):(RawMatchResult|null)
 * @phpstan-type RawEnumVariantMatch Closure(class-string<BackedEnum> $enumFQN):(RawMatchResult|null)
 * @phpstan-type RawEnumVariantConfig array{
 *     rule: RawEnumVariantRule,
 *     resolver: RawEnumVariantResolver,
 * }
 */
class RefsResolver
{
    /**
     * @param array<RawMapperVariantConfig|RawMapperVariantMatch>|RefRuleRegistry<Mapper> $mapperVariants
     * @param array<RawEnumVariantConfig|RawEnumVariantMatch>|RefRuleRegistry<BackedEnum> $enumVariants
     */
    public function __construct(
        protected readonly array|RefRuleRegistry $mapperVariants,
        protected readonly array|RefRuleRegistry $enumVariants
    ) {
    }

    /**
     * @param class-string<Mapper>|class-string<BackedEnum> $target $target
     */
    public function resolveImport(RefTypes $type, string $target): string
    {
        [$namespace, $mapperName] = $this->resolve($type, $target);

        return "import('{$namespace}').{$mapperName}";
    }

    /**
     * @param class-string<Mapper>|class-string<BackedEnum> $target
     *
     * @return array{string, string}
     */
    public function resolve(RefTypes $type, string $target): array
    {
        if ($type === RefTypes::ENUM) {
            /** @var class-string<BackedEnum> $target */
            return $this->resolveEnumRef($target);
        }

        /** @var class-string<Mapper> $target */
        return $this->resolveMapperRef($target);
    }

    /**
     * @param class-string<BackedEnum> $enumFQN
     *
     * @return array{string, string}
     */
    protected function resolveEnumRef(string $enumFQN): array
    {
        /** @var string|string[]|null $namespaceSegments */
        $namespaceSegments = null;

        /** @var string|null $enumName */
        $enumName = null;

        if ($this->enumVariants instanceof RefRuleRegistry) {
            foreach ($this->enumVariants->rules as $refRule) {
                $resolvedValue = $refRule->resolve($enumFQN);

                if ($resolvedValue) {
                    $namespaceSegments = $resolvedValue->namespaceSegments;
                    $enumName = $resolvedValue->name;
                }
            }
        } else {
            foreach ($this->enumVariants as $variant) {
                if (is_callable($variant)) {
                    $resolvedValue = $variant($enumFQN);

                    if ($resolvedValue) {
                        [$namespaceSegments, $enumName] = $resolvedValue;
                    }
                } else {
                    if (is_callable($variant['rule'])) {
                        $isMatch = $variant['rule']($enumFQN);

                        $matches = null;
                    } else {
                        $isMatch = preg_match($variant['rule'], $enumFQN, $matches) > 0;
                    }

                    if ($isMatch) {
                        $resolvedValue = $variant['resolver']($matches, $enumFQN);

                        if ($resolvedValue) {
                            [$namespaceSegments, $enumName] = $resolvedValue;
                        }

                        break;
                    }
                }
            }
        }

        if (! $namespaceSegments) {
            throw new LogicException("Can't resolve \"{$enumFQN}\" namespace.");
        }

        if (! $enumName) {
            throw new LogicException("Can't resolve \"{$enumFQN}\" enum.");
        }

        $namespace = implode(
            '/',
            is_array($namespaceSegments) ? $namespaceSegments : [$namespaceSegments]
        );

        return [$namespace, $enumName];
    }

    /**
     * @param class-string<Mapper> $mapperFQN
     *
     * @return array{string, string}
     */
    protected function resolveMapperRef(string $mapperFQN): array
    {
        /** @var string|string[]|null $namespaceSegments */
        $namespaceSegments = null;

        /** @var string|null $mapperName */
        $mapperName = null;

        if ($this->mapperVariants instanceof RefRuleRegistry) {
            foreach ($this->mapperVariants->rules as $refRule) {
                $resolvedValue = $refRule->resolve($mapperFQN);

                if ($resolvedValue) {
                    $namespaceSegments = $resolvedValue->namespaceSegments;
                    $mapperName = $resolvedValue->name;
                }
            }
        } else {
            foreach ($this->mapperVariants as $variant) {
                if (is_callable($variant)) {
                    $resolvedValue = $variant($mapperFQN);

                    if ($resolvedValue) {
                        [$namespaceSegments, $mapperName] = $resolvedValue;
                    }
                } else {
                    if (is_callable($variant['rule'])) {
                        $isMatch = $variant['rule']($mapperFQN);

                        $matches = null;
                    } else {
                        $isMatch = preg_match($variant['rule'], $mapperFQN, $matches) > 0;
                    }

                    if ($isMatch) {
                        $resolvedValue = $variant['resolver']($matches, $mapperFQN);

                        if ($resolvedValue) {
                            [$namespaceSegments, $mapperName] = $resolvedValue;
                        }

                        break;
                    }
                }
            }
        }

        if (! $namespaceSegments) {
            throw new LogicException("Can't resolve \"{$mapperFQN}\" namespace.");
        }

        if (! $mapperName) {
            throw new LogicException("Can't resolve \"{$mapperFQN}\" mapper.");
        }

        $namespace = implode(
            '/',
            array_map(
                Str::kebab(...),
                is_array($namespaceSegments) ? $namespaceSegments : [$namespaceSegments]
            )
        );

        return [$namespace, $mapperName];
    }
}
