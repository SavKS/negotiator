<?php

namespace Savks\Negotiator\Support\DTO\Utils;

use Closure;
use Savks\Negotiator\Exceptions\UnexpectedValue;
use Savks\Negotiator\Support\Types\ConstRecordType;

use Savks\Negotiator\Support\DTO\{
    Cast,
    WorkWithAccessor
};

class Spread
{
    use WorkWithAccessor;

    protected array $sourcesTrace = [];

    /**
     * @param array<string, Cast> $schema
     */
    public function __construct(
        protected readonly array $schema,
        protected readonly string|Closure|null $accessor = null
    ) {
    }

    public function applyTo(array &$data, mixed $source, array $sourcesTrace): void
    {
        $value = static::resolveValueFromAccessor(
            $this->accessor,
            $source,
            $sourcesTrace
        );

        $sourcesTrace[] = $source;

        foreach ($this->schema as $field => $fieldValue) {
            if ($fieldValue instanceof Spread) {
                $fieldValue->applyTo($value, $sourcesTrace, $data);
            } else {
                if (! $fieldValue instanceof Cast) {
                    throw new UnexpectedValue(Cast::class, $fieldValue);
                }

                try {
                    $data[$field] = $fieldValue->resolve($value, $sourcesTrace);
                } catch (UnexpectedValue $e) {
                    throw UnexpectedValue::wrap($e, $field);
                }
            }
        }
    }

    public function applyTypesTo(ConstRecordType $resultType): void
    {
        foreach ($this->schema as $field => $value) {
            if ($value instanceof Spread) {
                $value->applyTypesTo($resultType);
            } else {
                $resultType->add(
                    $field,
                    $value->compileTypes()
                );
            }
        }
    }
}
