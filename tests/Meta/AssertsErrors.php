<?php


namespace Seier\Resting\Tests\Meta;


use Closure;
use Seier\Resting\Exceptions\ValidationException;

trait AssertsErrors
{

    protected function assertDoesNotThrowValidationException(Closure $code)
    {
        $code();
        $this->assertTrue(true);
    }

    protected function assertThrowsValidationException(Closure $code): ValidationException
    {
        try {
            $code();
            $this->fail('Did not throw ValidationException');
        } catch (ValidationException $exception) {
            return $exception;
        }
    }

    protected function assertHasError(ValidationException|array $exception, string $class, string $path = '')
    {
        $errors = is_array($exception) ? $exception : $exception->getErrors();
        foreach ($errors as $error) {
            $actualPath = $error->getPath();
            $actualClass = $error::class;
            if ($actualPath === $path && $actualClass === $class) {
                $this->assertTrue(true);
                return;
            }
        }

        $this->fail("Could not find validation error at $path with type $class");
    }
}