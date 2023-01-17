<?php

namespace Savks\Negotiator\Providers;

use Illuminate\Support\ServiceProvider;
use Savks\Negotiator\TypeGeneration\MapperAliases;

class NegotiatorServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(MapperAliases::class);
    }
}
