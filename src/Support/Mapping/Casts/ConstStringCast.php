<?php

namespace Savks\Negotiator\Support\Mapping\Casts;

use Savks\Negotiator\Support\TypeGeneration\Types\{
    ConstStringType,
    StringType
};

class ConstStringCast extends ConstCast
{
    public function __construct(
        protected readonly string $value,
        protected readonly bool $asAnyString
    ) {
    }

    public function originalValue(): string
    {
        return $this->value;
    }

    protected function finalize(mixed $source, array $sourcesTrace): string
    {
        return $this->value;
    }

    protected function types(): StringType|ConstStringType
    {
        return $this->asAnyString ? new StringType() : new ConstStringType($this->value);
    }
}
