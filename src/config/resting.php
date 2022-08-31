<?php

return [
    'api_name' => 'REST API',

    'version' => '1',

    'validation_exception' => \Seier\Resting\Exceptions\ValidationException::class,

    'documentation' => [
        'servers' => [
            [
                'url' => env('APP_URL', 'http://localhost'),
                'description' => 'Local'
            ]
        ],
    ],
];
