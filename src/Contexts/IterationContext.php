<?php

namespace Savks\Negotiator\Contexts;

use Savks\PhpContexts\Context;

class IterationContext extends Context
{
    public function __construct(public readonly int $index)
    {
    }
}
