<?php

namespace Savks\Negotiator\Support\TypeGeneration;

use BackedEnum;
use Closure;
use Illuminate\Support\Str;
use LogicException;
use Savks\Negotiator\Enums\RefTypes;

class RefsResolver
{
    /**
     * @param list<array{
     *     pattern: string,
     *     resolver: Closure(array, class-string): (array|null),
     * }> $mapperVariants
     * @param list<array{
     *     pattern: string,
     *     resolver: Closure(array, class-string<BackedEnum>): (array|null),
     * }> $enumVariants
     * @param array{
     *     mappers?: array{
     *         basePathSegments: string[],
     *     },
     *     enums?: array{
     *         basePathSegments: string[],
     *     },
     * }|null $config
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
            if (\preg_match($variant['pattern'], $enumFQN, $matches) > 0) {
                $resolvedValue = $variant['resolver']($matches, $enumFQN);

                if ($resolvedValue) {
                    [$namespaceSegments, $enumName] = $resolvedValue;
                }

                break;
            }
        }

        if (! $enumName) {
            throw new LogicException("Can't resolve \"{$enumFQN}\" enum.");
        }

        return [
            \implode('/', [
                ...($this->config['enums']['basePathSegments'] ?? ['enums']),
                ...\array_map(
                    Str::kebab(...),
                    $namespaceSegments
                ),
            ]),
            $enumName,
        ];
    }

    protected function resolveMapperRef(string $mapperFQN): array
    {
        $namespaceSegments = null;
        $mapperName = null;

        foreach ($this->mapperVariants as $variant) {
            if (\preg_match($variant['pattern'], $mapperFQN, $matches) > 0) {
                $resolvedValue = $variant['resolver']($matches, $mapperFQN);

                if ($resolvedValue) {
                    [$namespaceSegments, $mapperName] = $resolvedValue;
                }

                break;
            }
        }

        if (! $mapperName) {
            throw new LogicException("Can't resolve \"{$mapperFQN}\" mapper.");
        }

        $namespace = \implode(
            '/',
            $namespaceSegments ?
                [
                    ...($this->config['mappers']['basePathSegments'] ?? ['@dto']),
                    ...\array_map(
                        Str::kebab(...),
                        $namespaceSegments
                    ),
                ] :
                $this->config['mappers']['basePathSegments'] ?? ['@dto']
        );

        return [$namespace, $mapperName];
    }
}
