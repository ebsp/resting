<?php


namespace Seier\Resting\Tests\Validation\Secondary\Anonymous;


use Seier\Resting\Tests\TestCase;
use Seier\Resting\Validation\Secondary\Anonymous\AnonymousValidationError;

class AnonymousValidationErrorTest extends TestCase
{

    public function testGetMessageReturnsProvidedMessage()
    {
        $expected = $this->faker->text;
        $instance = new AnonymousValidationError($expected);

        $this->assertEquals($expected, $instance->getMessage());
    }
}