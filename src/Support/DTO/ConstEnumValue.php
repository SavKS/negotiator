<?php

namespace Savks\Negotiator\Support\DTO;

use BackedEnum;
use Closure;
use Savks\Negotiator\Contexts\TypeGenerationContext;
use Savks\Negotiator\Exceptions\UnexpectedValue;
use Savks\PhpContexts\Context;
use StringBackedEnum;

use Savks\Negotiator\Support\Types\{
    AliasType,
    NumberType,
    StringType
};

class ConstEnumValue extends NullableValue
{
    public function __construct(protected BackedEnum $case)
    {
    }

    protected function finalize(): string|int|null
    {
        return $this->case->value;
    }

    protected function types(): AliasType|StringType|NumberType
    {
        $enumRef = Context::use(TypeGenerationContext::class)->resolveEnumRef(
            $this->case::class
        );

        if ($enumRef) {
            return new AliasType("{$enumRef}.{$this->case->name}");
        }

        return $this->case instanceof StringBackedEnum ? new StringType() : new NumberType();
    }
}
