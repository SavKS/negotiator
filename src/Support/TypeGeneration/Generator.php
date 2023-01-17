<?php

namespace Savks\Negotiator\Support\TypeGeneration;

use Illuminate\Support\Str;
use Savks\Negotiator\Contexts\TypeGenerationContext;

use Savks\Negotiator\TypeGeneration\{
    Faker,
    Generator as TypeGenerator
};

class Generator
{
    /**
     * @var Target[]
     */
    protected array $targets = [];

    public function __construct()
    {
    }

    public function addTarget(Target $target): static
    {
        $this->targets[] = $target;

        return $this;
    }

    public function saveTo(string $destPath): bool
    {
        $schema = (new TypeGenerationContext())->wrap(function () {
            $result = [];

            $faker = new Faker();

            foreach ($this->targets as $target) {
                foreach ($target->mappersMap as $name => $mapperFQN) {
                    $types = $faker->makeMapper($mapperFQN)->map()->compileTypes();

                    $content = (new TypeGenerator($types))->generate();

                    $result[$target->namespace][] = "export type {$name} = {$content};";
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

            $lines[] = \implode(
                "\n\n",
                $namespace ?
                    \array_map(
                        fn (string $value) => Str::padLeft($value, 4),
                        $types
                    ) :
                    $types
            );

            if ($namespace) {
                $lines[] = "}\n";
            }

            $blocks[] = \implode("\n", $lines);
        }

        return \implode("\n\n", $blocks);
    }

    protected function write(string $content, string $destPath): bool
    {
        $destDir = \dirname($destPath);

        if (! \is_dir($destDir)) {
            \mkdir($destDir, recursive: true);
        }

        $status = \file_put_contents($destPath, $content);

        return $status !== false;
    }
}
