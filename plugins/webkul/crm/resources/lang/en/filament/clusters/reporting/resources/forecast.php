<?php

return [
    'model-label'        => 'Forecast',
    'plural-model-label' => 'Forecasts',

    'title' => 'Forecast',

    'navigation' => [
        'title' => 'Forecast',
    ],

    'kanban' => [
        'empty-state' => [
            'heading'     => 'No opportunities to forecast',
            'description' => 'Set an expected closing date on your opportunities to see them here.',
        ],

        'notifications' => [
            'moved'        => 'Expected closing date updated',
            'unauthorized' => 'You are not allowed to change this opportunity.',
        ],
    ],
];
