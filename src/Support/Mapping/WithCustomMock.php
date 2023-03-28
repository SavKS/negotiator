<?php

namespace Savks\Negotiator\Support\Mapping;

interface WithCustomMock
{
    public static function mock(): static|self;
}
