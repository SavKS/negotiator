<?php

namespace Savks\Negotiator\Support\TypeGeneration;

use BackedEnum;
use Closure;
use Illuminate\Support\Str;
use LogicException;
use Savks\Negotiator\Enums\RefTypes;
use Savks\Negotiator\Support\Mapping\Mapper;

class RefsResolver
{
    /**
     * @param list<array{
     *     rule: string | (Closure(class-string): bool),
     *     resolver: Closure(array, class-string<Mapper>): (list<string[]|string>|null),
     * } | (Closure(class-string<Mapper>): (list<string[]|string>|null))> $mapperVariants
     * @param list<array{
     *     rule: string | (Closure(class-string): bool),
     *     resolver: Closure(array, class-string<BackedEnum>): (list<string[]|string>|null),
     * } | (Closure(class-string<BackedEnum>): (list<string[]|string>|null))> $enumVariants
     */
    public function __construct(
        protected readonly array $mapperVariants,
        protected readonly array $enumVariants,
        protected readonly ?array $config = null
    ) {
    }

    public function resolve(RefTypes $type, string $target): ?array
    {
        return match ($type) {
            RefTypes::ENUM => $this->resolveEnumRef($target),
            RefTypes::MAPPER => $this->resolveMapperRef($target)
        };
    }

    public function resolveImport(RefTypes $type, string $target): ?string
    {
        $parts = $this->resolve($type, $target);

        if (! $parts) {
            return null;
        }

        [$namespace, $mapperName] = $parts;

        return "import('{$namespace}').{$mapperName}";
    }

    protected function resolveEnumRef(string $enumFQN): array
    {
        $namespaceSegments = null;
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

        if (! $enumName) {
            throw new LogicException("Can't resolve \"{$enumFQN}\" enum.");
        }

        return [
            implode(
                '/',
                array_map(
                    Str::kebab(...),
                    $namespaceSegments
                )
            ),
            $enumName,
        ];
    }

    protected function resolveMapperRef(string $mapperFQN): array
    {
        $namespaceSegments = null;
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

        if (! $mapperName) {
            throw new LogicException("Can't resolve \"{$mapperFQN}\" mapper.");
        }

        $namespace = implode(
            '/',
            array_map(
                Str::kebab(...),
                $namespaceSegments
            )
        );

        return [$namespace, $mapperName];
    }
}
