<?php

namespace Savks\Negotiator\Support\TypeGeneration\TypeScript;

use BackedEnum;
use Closure;
use Illuminate\Support\Str;
use LogicException;
use Savks\Negotiator\Enums\RefTypes;
use Savks\Negotiator\Support\Mapping\Mapper;

/**
 * @phpstan-type MatchResult array{string|string[],string}
 * @phpstan-type MapperVariantRule string|(Closure(class-string<Mapper> $mapperFQN):bool)
 * @phpstan-type MapperVariantResolver Closure(string[]|null $matches, class-string<Mapper> $mapperFQN):(MatchResult|null)
 * @phpstan-type MapperVariantMatch Closure(class-string<Mapper>):(MatchResult|null)
 * @phpstan-type MapperVariantConfig array{
 *     rule: EnumVariantRule,
 *     resolver: EnumVariantResolver
 * }
 * @phpstan-type EnumVariantRule string|(Closure(class-string<BackedEnum> $enumFQN):bool)
 * @phpstan-type EnumVariantResolver Closure(string[]|null $matches, class-string<BackedEnum> $enumFQN):(MatchResult|null)
 * @phpstan-type EnumVariantMatch Closure(class-string<BackedEnum> $enumFQN):(MatchResult|null)
 * @phpstan-type EnumVariantConfig array{
 *     rule: MapperVariantRule,
 *     resolver: MapperVariantResolver,
 * }
 */
class RefsResolver
{
    /**
     * @param list<EnumVariantConfig|MapperVariantMatch> $mapperVariants
     * @param list<MapperVariantConfig|EnumVariantMatch> $enumVariants
     */
    public function __construct(
        protected readonly array $mapperVariants,
        protected readonly array $enumVariants
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
