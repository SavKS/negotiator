<?php

namespace Savks\Negotiator\Support\TypeGeneration\TypeScript\RefResolver;

final readonly class RefMatchResult
{
    /**
     * @var string[]
     */
    public array $namespaceSegments;

    /**
     * @param string[]|string $namespaceSegments
     */
    public function __construct(
        array|string $namespaceSegments,
        public string $name
    ) {
        $this->namespaceSegments = is_array($namespaceSegments) ? $namespaceSegments : [$namespaceSegments];
    }
}
