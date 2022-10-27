<?php

use Seier\Resting\Exceptions\EnvFunctionMissing;

if (!function_exists('env')) {
    throw EnvFunctionMissing::cast();
}

return [
    'api_name' => 'REST API',

    'version' => '1',

    'validation_exception' => \Seier\Resting\Exceptions\ValidationException::class,

    'documentation' => [
        'servers' => [
            [
                'url' => call_user_func('env', 'APP_URL', 'http://localhost'),
                'description' => 'Local'
            ]
        ],
    ],
];
