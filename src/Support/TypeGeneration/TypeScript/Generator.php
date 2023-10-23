<?php

namespace Savks\Negotiator\Support\TypeGeneration\TypeScript;

use BackedEnum;
use Closure;
use Illuminate\Support\Str;
use RuntimeException;
use Savks\Negotiator\Contexts\TypeGenerationContext;
use Savks\Negotiator\Enums\RefTypes;
use Throwable;

use Savks\Negotiator\Support\Mapping\{
    GenericDeclaration,
    Mapper
};
use Savks\Negotiator\Support\TypeGeneration\{
    TypeScript\TypeProcessor as TypeGenerator,
    Faker
};

class Generator
{
    /**
     * @var Target[]
     */
    protected array $targets = [];

    /**
     * @param (Closure(RefTypes, class-string<Mapper|BackedEnum>): string)|null $refsResolver
     */
    public function __construct(protected readonly ?Closure $refsResolver = null)
    {
    }

    public function addTarget(Target $target): static
    {
        $this->targets[] = $target;

        return $this;
    }

    public function saveTo(string $destPath): bool
    {
        $schema = (new TypeGenerationContext($this->refsResolver))->wrap(function () use ($destPath) {
            $result = [];

            $faker = new Faker();

            foreach ($this->targets as $target) {
                foreach ($target->mappersMap as $name => $mapper) {
                    try {
                        if ($mapper instanceof Closure) {
                            $mapper = $mapper();
                        }

                        if (! $mapper instanceof Mapper) {
                            $mapper = $faker->makeMapper($mapper);
                        }

                        $generics = $mapper->declareGenerics();

                        $types = $mapper::schema()->compileTypes();
                    } catch (Throwable $e) {
                        $safeDestPath = ltrim(
                            str_replace(
                                base_path(),
                                '',
                                $destPath
                            ),
                            '/'
                        );

                        throw new RuntimeException(
                            sprintf(
                                "Can't generate types file \"%s\" for mapper \"%s\". Message: %s.",
                                $safeDestPath,
                                is_object($mapper) ? get_class($mapper) : $mapper,
                                $e->getMessage()
                            ),
                            previous: $e
                        );
                    }

                    $content = (new TypeGenerator($types))->process();

                    if ($generics) {
                        $result[$target->namespace][] = "export type {$name}<{$this->stringifyGenerics($generics)}> = {$content};";
                    } else {
                        $result[$target->namespace][] = "export type {$name} = {$content};";
                    }
                }
            }

            return $result;
        });

        return $this->write(
            $this->generateCode($schema),
            $destPath
        );
    }

    /**
     * @param GenericDeclaration[] $generics
     */
    protected function stringifyGenerics(array $generics): string
    {
        $parts = [];

        foreach ($generics as $generic) {
            $parts[] = $generic->stringify($this->refsResolver);
        }

        return implode(', ', $parts);
    }

    protected function write(string $content, string $destPath): bool
    {
        $destDir = dirname($destPath);

        if (! is_dir($destDir)) {
            mkdir($destDir, recursive: true);
        }

        $status = file_put_contents($destPath, $content);

        return $status !== false;
    }

    /**
     * @param array<string, string[]> $schema
     */
    protected function generateCode(array $schema): string
    {
        $blocks = [];

        foreach ($schema as $namespace => $types) {
            $lines = [];

            if ($namespace) {
                $lines[] = "declare module '{$namespace}' {";
            }

            $lines[] = implode(
                "\n\n",
                $namespace ?
                    array_map(
                        fn (string $value) => Str::padLeft($value, 4),
                        $types
                    ) :
                    $types
            );

            if ($namespace) {
                $lines[] = "}\n";
            }

            $blocks[] = implode("\n", $lines);
        }

        return implode("\n\n", $blocks);
    }
}
