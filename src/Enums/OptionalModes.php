<?php

namespace Savks\Negotiator\Enums;

enum OptionalModes
{
    case FALSE_AS_OPTIONAL;
    case EMPTY_STRING_AS_OPTIONAL;
    case EMPTY_ARRAY_AS_OPTIONAL;
}
