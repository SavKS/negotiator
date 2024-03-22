<?php

namespace Savks\Negotiator\Support\Mapping\Casts;

interface ForwardedCast
{
    public function nestedCast(): Cast;
}
