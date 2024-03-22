<?php

namespace Savks\Negotiator\Support\Mapping\Casts\ObjectUtils;

use Closure;
use Savks\Negotiator\Support\TypeGeneration\Types\ObjectType;
use stdClass;
use Throwable;

use Savks\Negotiator\Exceptions\{
    InternalException,
    UnexpectedValue
};
use Savks\Negotiator\Support\Mapping\Casts\{
    Cast,
    WorkWithAccessor,
    WorkWithOptionalFields
};

class Spread
{
    use WorkWithAccessor;
    use WorkWithOptionalFields;

    protected array $sourcesTrace = [];

    /**
     * @param array<string, Cast|Spread> $schema
     */
    public function __construct(
        protected readonly array $schema,
        protected readonly string|Closure|null $accessor = null
    ) {
    }

    public function applyTo(stdClass $data, mixed $source, array $sourcesTrace): void
    {
        $value = static::resolveValueFromAccessor(
            $this->accessor,
            $source,
            $sourcesTrace
        );

        $sourcesTrace[] = $source;

        foreach ($this->schema as $field => $fieldValue) {
            if ($fieldValue instanceof Spread) {
                $fieldValue->applyTo($data, $value, $sourcesTrace);
            } else {
                if (! $fieldValue instanceof Cast) {
                    throw new UnexpectedValue(Cast::class, $fieldValue);
                }

                try {
                    $resolvedValue = $fieldValue->resolve($value, $sourcesTrace);

                    if (! $this->needSkip($resolvedValue, $fieldValue)) {
                        $data->{$field} = $resolvedValue;
                    }
                } catch (UnexpectedValue $e) {
                    throw UnexpectedValue::wrap($e, $field);
                } catch (Throwable $e) {
                    throw InternalException::wrap($e, $field);
                }
            }
        }
    }

    public function applyTypesTo(ObjectType $resultType): void
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
