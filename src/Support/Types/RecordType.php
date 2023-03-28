<?php

namespace Savks\Negotiator\Support\Types;

class RecordType extends Type
{
    public function __construct(
        public readonly Type|Types $keyType = new StringType(),
        public readonly Type|Types $valueType = new AnyType()
    ) {
    }
}
