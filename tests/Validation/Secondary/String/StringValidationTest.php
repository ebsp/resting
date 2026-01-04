<?php


namespace Seier\Resting\Tests\Validation\Secondary\String;


use Seier\Resting\Tests\TestCase;
use Seier\Resting\Tests\Meta\AssertsErrors;
use Seier\Resting\Tests\Meta\MockPrimaryValidator;
use Seier\Resting\Validation\Secondary\String\StringRegexValidationError;
use Seier\Resting\Validation\Secondary\String\StringLengthValidationError;
use Seier\Resting\Validation\Secondary\String\StringMinLengthValidationError;
use Seier\Resting\Validation\Secondary\String\StringMaxLengthValidationError;

class StringValidationTest extends TestCase
{

    use AssertsErrors;

    private MockPrimaryValidator $validator;
    private StringValidationTestBench $instance;

    public function setUp(): void
    {
        parent::setUp();

        $this->validator = new MockPrimaryValidator();
        $this->instance = new StringValidationTestBench($this->validator);
    }

    public function testLengthWhenPasses()
    {
        $this->instance->length(2);

        $this->assertEmpty($this->validator->validate('..'));
    }

    public function testLengthWhenFails()
    {
        $this->instance->length(2);

        $this->assertNotEmpty($errors = $this->validator->validate('.'));
        $this->assertHasError($errors, StringLengthValidationError::class);

        $this->assertNotEmpty($errors = $this->validator->validate('...'));
        $this->assertHasError($errors, StringLengthValidationError::class);
    }

    public function testNotEmptyWhenPasses()
    {
        $this->instance->notEmpty();

        $this->assertEmpty($this->validator->validate('.'));
        $this->assertEmpty($this->validator->validate('..'));
    }

    public function testNotEmptyWhenFails()
    {
        $this->instance->notEmpty();

        $this->assertNotEmpty($errors = $this->validator->validate(''));
        $this->assertHasError($errors, StringMinLengthValidationError::class);
    }

    public function testMinLengthWhenPasses()
    {
        $this->instance->minLength(2);

        $this->assertEmpty($this->validator->validate('..'));
        $this->assertEmpty($this->validator->validate('...'));
    }

    public function testMinLengthWhenFails()
    {
        $this->instance->minLength(2);

        $this->assertNotEmpty($errors = $this->validator->validate(''));
        $this->assertHasError($errors, StringMinLengthValidationError::class);

        $this->assertNotEmpty($errors = $this->validator->validate('.'));
        $this->assertHasError($errors, StringMinLengthValidationError::class);
    }

    public function testMaxLengthWhenPasses()
    {
        $this->instance->maxLength(2);

        $this->assertEmpty($this->validator->validate(''));
        $this->assertEmpty($this->validator->validate('.'));
        $this->assertEmpty($this->validator->validate('..'));
    }

    public function testMaxLengthWhenFails()
    {
        $this->instance->maxLength(2);

        $this->assertNotEmpty($errors = $this->validator->validate('...'));
        $this->assertHasError($errors, StringMaxLengthValidationError::class);
    }

    public function testBetweenLengthWhenPasses()
    {
        $this->instance->betweenLength(2, 4);

        $this->assertEmpty($this->validator->validate('..'));
        $this->assertEmpty($this->validator->validate('...'));
        $this->assertEmpty($this->validator->validate('....'));
    }

    public function testBetweenLengthWhenFails()
    {
        $this->instance->betweenLength(2, 4);

        $this->assertNotEmpty($errors = $this->validator->validate('.'));
        $this->assertHasError($errors, StringMinLengthValidationError::class);

        $this->assertNotEmpty($errors = $this->validator->validate('.....'));
        $this->assertHasError($errors, StringMaxLengthValidationError::class);
    }

    public function testMatchesWhenPasses()
    {
        $this->instance->matches('/^\.*$/');

        $this->assertEmpty($this->validator->validate('.'));
        $this->assertEmpty($this->validator->validate('...'));
    }

    public function testMatchesWhenFails()
    {
        $this->instance->matches('/^\.*$/');

        $this->assertNotEmpty($errors = $this->validator->validate('_'));
        $this->assertHasError($errors, StringRegexValidationError::class);

        $this->assertNotEmpty($errors = $this->validator->validate('._.'));
        $this->assertHasError($errors, StringRegexValidationError::class);
    }

    public function testDigitsWhenPasses()
    {
        $this->instance->digits();

        $this->assertEmpty($this->validator->validate('1'));
        $this->assertEmpty($this->validator->validate('190'));
    }

    public function testDigitsWithLengthRequirementWhenPasses()
    {
        $this->instance->digits(2);

        $this->assertEmpty($this->validator->validate('45'));
    }

    public function testDigitsWhenFails()
    {
        $this->instance->digits();

        $this->assertNotEmpty($errors = $this->validator->validate(''));
        $this->assertHasError($errors, StringRegexValidationError::class);

        $this->assertNotEmpty($errors = $this->validator->validate('a'));
        $this->assertHasError($errors, StringRegexValidationError::class);

        $this->assertNotEmpty($errors = $this->validator->validate('.'));
        $this->assertHasError($errors, StringRegexValidationError::class);

        $this->assertNotEmpty($errors = $this->validator->validate('1a'));
        $this->assertHasError($errors, StringRegexValidationError::class);

        $this->assertNotEmpty($errors = $this->validator->validate('a1'));
        $this->assertHasError($errors, StringRegexValidationError::class);
    }

    public function testDigitsWithLengthRequirementWhenFails()
    {
        $this->instance->digits(2);

        $this->assertNotEmpty($errors = $this->validator->validate('7'));
        $this->assertHasError($errors, StringLengthValidationError::class);

        $this->assertNotEmpty($errors = $this->validator->validate('312'));
        $this->assertHasError($errors, StringLengthValidationError::class);
    }

    public function testNoWhitespaceWhenPasses()
    {
        $this->instance->noWhitespace();

        $this->assertEmpty($this->validator->validate('.'));
        $this->assertEmpty($this->validator->validate('a'));
    }

    public function testNoWhitespaceWhenFails()
    {
        $this->instance->noWhitespace();

        $this->assertNotEmpty($errors = $this->validator->validate(' '));
        $this->assertHasError($errors, StringRegexValidationError::class);

        $this->assertNotEmpty($errors = $this->validator->validate("\t"));
        $this->assertHasError($errors, StringRegexValidationError::class);

        $this->assertNotEmpty($errors = $this->validator->validate("\r"));
        $this->assertHasError($errors, StringRegexValidationError::class);

        $this->assertNotEmpty($errors = $this->validator->validate("\n"));
        $this->assertHasError($errors, StringRegexValidationError::class);

        $this->assertNotEmpty($errors = $this->validator->validate('. .'));
        $this->assertHasError($errors, StringRegexValidationError::class);

        $this->assertNotEmpty($errors = $this->validator->validate(' .'));
        $this->assertHasError($errors, StringRegexValidationError::class);

        $this->assertNotEmpty($errors = $this->validator->validate('. '));
        $this->assertHasError($errors, StringRegexValidationError::class);
    }

    public function testHexColorAcceptingShortWhenPasses()
    {
        $this->instance->hexColor();

        $this->assertEmpty($this->validator->validate('#ABC'));
        $this->assertEmpty($this->validator->validate('#ABCDEF'));
        $this->assertEmpty($this->validator->validate('#000'));
        $this->assertEmpty($this->validator->validate('#000555'));
    }

    public function testHexColorRejectingShortWhenPasses()
    {
        $this->instance->hexColor(acceptShort: false);

        $this->assertEmpty($this->validator->validate('#ABCDEF'));
        $this->assertEmpty($this->validator->validate('#000555'));
    }

    public function testHexColorAcceptingShortWhenFails()
    {
        $this->instance->hexColor();

        $this->assertNotEmpty($errors = $this->validator->validate('#AB'));
        $this->assertHasError($errors, StringRegexValidationError::class);

        $this->assertNotEmpty($errors = $this->validator->validate('#'));
        $this->assertHasError($errors, StringRegexValidationError::class);

        $this->assertNotEmpty($errors = $this->validator->validate('000'));
        $this->assertHasError($errors, StringRegexValidationError::class);

        $this->assertNotEmpty($errors = $this->validator->validate('000555'));
        $this->assertHasError($errors, StringRegexValidationError::class);
    }

    public function testHexColorRejectingCodesWhenFails()
    {
        $this->instance->hexColor(acceptShort: false);

        $this->assertNotEmpty($errors = $this->validator->validate('#ABC'));
        $this->assertHasError($errors, StringRegexValidationError::class);

        $this->assertNotEmpty($errors = $this->validator->validate('#000'));
        $this->assertHasError($errors, StringRegexValidationError::class);
    }
}