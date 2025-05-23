<?php

namespace Savks\Negotiator\Support\Mapping\Casts;

use BackedEnum;
use ReflectionEnum;
use ReflectionNamedType;
use Savks\Negotiator\Contexts\TypeGenerationContext;
use Savks\Negotiator\Enums\RefTypes;
use Savks\Negotiator\Support\TypeGeneration\Types\AliasType;
use Savks\Negotiator\Support\TypeGeneration\Types\NumberType;
use Savks\Negotiator\Support\TypeGeneration\Types\StringType;
use Savks\PhpContexts\Context;

class ConstEnumCast extends ConstCast
{
    public function __construct(protected readonly BackedEnum $case)
    {
    }

    public function originalValue(): BackedEnum
    {
        return $this->case;
    }

    protected function finalize(mixed $source, array $sourcesTrace): string|int|null
    {
        $this->assertMatching(
            $source,
            $sourcesTrace,
            $this->case::class . '::' . $this->case->name
        );

        return $this->case->value;
    }

    protected function types(): AliasType|StringType|NumberType
    {
        /** @var TypeGenerationContext $typeGenerationContext */
        $typeGenerationContext = Context::use(TypeGenerationContext::class);

        $enumRef = $typeGenerationContext->resolveEnumRef($this->case::class);

        if ($enumRef) {
            return new AliasType("{$enumRef}.{$this->case->name}", ref: [
                'type' => RefTypes::ENUM,
                'fqn' => $this->case::class,
            ]);
        }

        /** @var ReflectionNamedType $type */
        $type = (new ReflectionEnum($this->case))->getBackingType();

        return $type->getName() === 'string' ? new StringType() : new NumberType();
    }
}
