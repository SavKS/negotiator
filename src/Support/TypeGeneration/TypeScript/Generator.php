<?php

namespace Savks\Negotiator\Support\TypeGeneration\TypeScript;

use BackedEnum;
use Closure;
use Illuminate\Support\Str;
use LogicException;
use ReflectionClass;
use RuntimeException;
use Savks\Negotiator\Contexts\TypeGenerationContext;
use Savks\Negotiator\Enums\RefTypes;
use Savks\Negotiator\Support\Mapping\Casts\Cast;
use Savks\Negotiator\Support\Mapping\GenericDeclaration;
use Savks\Negotiator\Support\Mapping\Mapper;
use Savks\Negotiator\Support\TypeGeneration\Types\AliasType;
use Savks\Negotiator\Support\TypeGeneration\Types\Types;
use Savks\Negotiator\Support\TypeGeneration\TypeScript\TypeProcessor as TypeGenerator;
use Throwable;

class Generator
{
    /**
     * @var Target[]
     */
    protected array $targets = [];

    /**
     * @param (Closure(RefTypes $refType, class-string<Mapper>|class-string<BackedEnum> $target): string)|null $refsResolver
     */
    public function __construct(protected readonly ?Closure $refsResolver = null)
    {
    }

    public function addTarget(Target $target): static
    {
        $this->targets[] = $target;

        return $this;
    }

    public function saveTo(string $destPath, bool $force = false): bool
    {
        $schema = (new TypeGenerationContext($this->refsResolver))->wrap(function () use ($destPath) {
            $result = [];

            foreach ($this->targets as $target) {
                /** @var class-string<Mapper>|Cast $mapperOrSchema */
                foreach ($target->mappersMap as $name => $mapperOrSchema) {
                    try {
                        if ($mapperOrSchema instanceof Cast) {
                            $generics = [];

                            $types = $mapperOrSchema->compileTypes();
                        } else {
                            $mapperRef = new ReflectionClass($mapperOrSchema);

                            if (! $mapperRef->isFinal() && ! $mapperRef->isAnonymous()) {
                                $mapperFQN = $mapperOrSchema;

                                throw new LogicException("Mapper \"{$mapperFQN}\" should be marked as \"final\".");
                            }

                            $forcedType = $mapperOrSchema::as();

                            if ($forcedType) {
                                $generics = [];

                                $types = is_string($forcedType)
                                    ? new Types([new AliasType($forcedType)])
                                    : $forcedType->compileTypes();
                            } else {
                                $generics = $mapperOrSchema::declareGenerics();

                                $types = $mapperOrSchema::schema()->compileTypes();
                            }
                        }
                    } catch (Throwable $e) {
                        $safeDestPath = ltrim(
                            str_replace(
                                base_path(),
                                '',
                                $destPath
                            ),
                            '/'
                        );

                        if ($mapperOrSchema instanceof Cast) {
                            throw new RuntimeException(
                                sprintf(
                                    "Can't generate types file \"%s\" for custom schema. Message: %s.",
                                    $safeDestPath,
                                    $e->getMessage()
                                ),
                                previous: $e
                            );
                        } else {
                            throw new RuntimeException(
                                sprintf(
                                    "Can't generate types file \"%s\" for mapper \"%s\". Message: %s.",
                                    $safeDestPath,
                                    $mapperOrSchema,
                                    $e->getMessage()
                                ),
                                previous: $e
                            );
                        }
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
            $destPath,
            $force
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

    protected function write(string $content, string $destPath, bool $force): bool
    {
        if (
            ! $force
            && ! $this->isHashedContentChanged($content, $destPath)
        ) {
            return false;
        }

        $destDir = dirname($destPath);

        if (! is_dir($destDir)) {
            mkdir($destDir, recursive: true);
        }

        $status = file_put_contents(
            $destPath,
            $this->addHashToContent($content)
        );

        if (! $status) {
            throw new RuntimeException("Can't write to file \"{$destPath}\".");
        }

        return true;
    }

    protected function addHashToContent(string $content): string
    {
        return '/* sourceHash=' . sha1($content) . " */\n\n" . $content;
    }

    protected function isHashedContentChanged(string $content, string $filePath): bool
    {
        if (! file_exists($filePath)) {
            return true;
        }

        $fileContent = file_get_contents($filePath);

        if ($fileContent === false) {
            return true;
        }

        if (! preg_match('/^\/\* sourceHash=(?<hash>\w+) *\*\//', $fileContent, $matches)) {
            return true;
        }

        return $matches['hash'] !== sha1($content);
    }
}
