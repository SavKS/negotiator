<?php

use Savks\Negotiator\Enums\PerformanceTrackers;
use Savks\Negotiator\Performance\ClockworkProvider;

return [
    'debug' => [
        'enable' => env('NEGOTIATOR_DEBUG'),
        'performance' => [
            'providers' => [
                'current' => env('NEGOTIATOR_PERFORMANCE_PROVIDER', 'clockwork'),
                'available' => [
                    'clockwork' => ClockworkProvider::class,
                ],
            ],
            'trackers' => [
                PerformanceTrackers::MAPPERS->value => env('NEGOTIATOR_TRACK_MAPPERS_PERFORMANCE', true),
                PerformanceTrackers::CASTS->value => env('NEGOTIATOR_TRACK_CASTS_PERFORMANCE', true),
            ],
        ],
    ],

    'ignore_exceptions' => [],
];
