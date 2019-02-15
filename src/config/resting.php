<?php

return [
    'api_name' => 'Rest API',
    'version' => 0.1,
    'documentation' => [
        'servers' => [
            [
                'url' => env('APP_URL', 'http://localhost'),
                'description' => 'Local'
            ]
        ],
    ],
];
