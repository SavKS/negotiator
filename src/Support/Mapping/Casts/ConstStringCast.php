<?php

namespace Savks\Negotiator\Support\Mapping\Casts;

use Savks\Negotiator\Support\TypeGeneration\Types\ConstStringType;
use Savks\Negotiator\Support\TypeGeneration\Types\StringType;

class ConstStringCast extends ConstCast
{
    protected bool $asAnyString = false;

    public function __construct(
        protected readonly string $value,
        bool $asAnyString = false
    ) {
        $this->asAnyString = $asAnyString;
    }

    public function asAnyString(): static
    {
        $this->asAnyString = true;

        return $this;
    }

    public function originalValue(): string
    {
        return $this->value;
    }

    protected function finalize(mixed $source, array $sourcesTrace): string
    {
        $this->assertMatching($source, $sourcesTrace);

        return $this->value;
    }

    protected function types(): StringType|ConstStringType
    {
        return $this->asAnyString ? new StringType() : new ConstStringType($this->value);
    }
}
