<?php

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
                'mappers' => env('NEGOTIATOR_TRACK_MAPPERS_PERFORMANCE', true),
            ],
        ],
    ],
];
