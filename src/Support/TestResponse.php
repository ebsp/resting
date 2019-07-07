<?php

namespace Seier\Resting\Support;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Foundation\Testing\Assert as PHPUnit;
use Illuminate\Foundation\Testing\TestResponse as BaseTestResponse;

class TestResponse extends BaseTestResponse
{
    public function assertNestedJsonValidationErrors($errors, $group = 'body')
    {
        $errors = Arr::wrap($errors);

        PHPUnit::assertNotEmpty($errors, 'No validation errors were provided.');

        $jsonErrors = $this->json()['errors'][$group] ?? [];

        $errorMessage = $jsonErrors
            ? 'Response has the following JSON validation errors:'.
            PHP_EOL.PHP_EOL.json_encode($jsonErrors, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE).PHP_EOL
            : 'Response does not have JSON validation errors.';

        foreach ($errors as $key => $value) {
            PHPUnit::assertArrayHasKey(
                (is_int($key)) ? $value : $key,
                $jsonErrors,
                "Failed to find a validation error in the response for key: '{$value}'".PHP_EOL.PHP_EOL.$errorMessage
            );

            if (! is_int($key)) {
                foreach (Arr::wrap($jsonErrors[$key]) as $jsonErrorMessage) {
                    if (Str::contains($jsonErrorMessage, $value)) {
                        return $this;
                    }
                }

                PHPUnit::fail(
                    "Failed to find a validation error in the response for key and message: '$key' => '$value'".PHP_EOL.PHP_EOL.$errorMessage
                );
            }
        }

        return $this;
    }
}
