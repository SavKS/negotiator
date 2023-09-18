<?php

namespace Savks\Negotiator\Providers;

use Illuminate\Support\ServiceProvider;
use Savks\Negotiator\Mapping\SchemasRepository;
use Savks\Negotiator\Performance\Performance;
use Savks\Negotiator\Support\TypeGeneration\MapperAliases;

class NegotiatorServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(MapperAliases::class);
        $this->app->singleton(Performance::class);
        $this->app->singleton(SchemasRepository::class);

        $this->registerConfigs();
    }

    public function registerConfigs(): void
    {
        $config = dirname(__DIR__, 2) . '/resources/configs/negotiator.php';

        $this->publishes(
            [
                $config => config_path('negotiator.php'),
            ],
            'configs'
        );

        $this->mergeConfigFrom($config, 'negotiator');
    }
}
