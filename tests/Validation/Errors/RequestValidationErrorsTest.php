<?php

namespace Seier\Resting\Tests\Validation\Errors;

use Seier\Resting\Tests\TestCase;
use Seier\Resting\Exceptions\ValidationException;
use Seier\Resting\Validation\Errors\RequiredValidationError;
use Seier\Resting\Validation\Errors\RequestValidationErrors;

class RequestValidationErrorsTest extends TestCase
{
    public function testIsEmptyWhenNoErrors()
    {
        $errors = new RequestValidationErrors();

        $this->assertTrue($errors->isEmpty());
        $this->assertFalse($errors->isNotEmpty());
        $this->assertSame([], $errors->all());
    }

    public function testExposesErrorsGroupedBySource()
    {
        $body = new RequiredValidationError();
        $query = new RequiredValidationError();
        $param = new RequiredValidationError();

        $errors = new RequestValidationErrors(
            body: [$body],
            query: [$query],
            param: [$param],
        );

        $this->assertSame([$body], $errors->getBody());
        $this->assertSame([$query], $errors->getQuery());
        $this->assertSame([$param], $errors->getParam());
        $this->assertTrue($errors->isNotEmpty());
    }

    public function testAllMergesEverySource()
    {
        $body = new RequiredValidationError();
        $query = new RequiredValidationError();
        $param = new RequiredValidationError();

        $errors = new RequestValidationErrors(
            body: [$body],
            query: [$query],
            param: [$param],
        );

        $this->assertSame([$body, $query, $param], $errors->all());
    }

    public function testToExceptionContainsAllErrors()
    {
        $body = new RequiredValidationError();
        $query = new RequiredValidationError();

        $errors = new RequestValidationErrors(body: [$body], query: [$query]);

        $exception = $errors->toException();

        $this->assertInstanceOf(ValidationException::class, $exception);
        $this->assertSame([$body, $query], $exception->getErrors());
    }
}
