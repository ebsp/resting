<?php


namespace Seier\Resting\Validation\Errors;


use ReflectionClass;
use Seier\Resting\Support\HasPath;

class NotSubclassOfError implements ValidationError
{

    use HasPath;

    private ReflectionClass $expected;
    private mixed $actual;

    public function __construct(ReflectionClass $expected, mixed $value)
    {
        $this->expected = $expected;
        $this->actual = $value;
    }

    public function getMessage(): string
    {
        $expectedClass = $this->expected->getName();
        $actualClass = get_debug_type($this->actual);

        return "Expected resource to be instance of $expectedClass, received $actualClass instead.";
    }
}