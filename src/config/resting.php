<?php

return [
    'api_name' => 'Rest API',

    'version' => '1',

    'validation_exception' => \Seier\Resting\Exceptions\ValidationException::class,

    'form_request' => \Seier\Resting\Support\ResourceRequest::class,

    'documentation' => [
        'servers' => [
            [
                'url' => env('APP_URL', 'http://localhost'),
                'description' => 'Local'
            ]
        ],
    ],
];
