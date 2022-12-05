<?php

namespace Savks\Negotiator\Commands;

use Illuminate\Console\Command;
use Savks\Negotiator\Contexts\TypeGenerationContext;

use Savks\Negotiator\TypeGeneration\{
    Faker,
    Generator
};

class GenerateTypes extends Command
{
    protected $signature = 'negotiator:generate:types {mapper} {dest} {typeName} {--append}}';

    public function handle()
    {
        return (new TypeGenerationContext())->wrap(function () {
            $faker = new Faker();

            if (! \class_exists($this->argument('mapper'))) {
                $this->components->error("Undefined class \"{$this->argument('mapper')}\"");

                return self::FAILURE;
            }

            $mapper = $faker->makeMapper(
                $this->argument('mapper')
            );

            $value = $mapper->map();

            $types = $value->compileTypes();

            $content = (new Generator($types))->generate();

            $this->write($content);

            return self::SUCCESS;
        });
    }

    protected function write(string $content): void
    {
        $dir = \dirname(
            $this->argument('dest')
        );

        if (! \is_dir($dir)) {
            \mkdir($dir, recursive: true);
        }

        \file_put_contents(
            $this->argument('dest'),
            "export type {$this->argument('typeName')} = {$content};",
            $this->option('append') ? \FILE_APPEND : 0
        );

        $this->components->info("Types file saved to: {$this->argument('dest')}");
    }
}
