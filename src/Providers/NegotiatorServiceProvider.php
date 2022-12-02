<?php

namespace Savks\Negotiator\Providers;

use Illuminate\Support\ServiceProvider;
use Savks\Negotiator\Commands\GenerateTypes;

class NegotiatorServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->commands([
            GenerateTypes::class,
        ]);
    }
}
