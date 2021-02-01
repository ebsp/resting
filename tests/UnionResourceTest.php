<?php


namespace Seier\Resting\Tests;

use Seier\Resting\Fields\ResourceField;
use Seier\Resting\Tests\Meta\AssertsErrors;
use Seier\Resting\Tests\Meta\UnionResourceA;
use Seier\Resting\Tests\Meta\UnionResourceB;
use Seier\Resting\Fields\ResourceArrayField;
use Seier\Resting\Tests\Meta\UnionResourceBase;
use Seier\Resting\Validation\Errors\NullableValidationError;
use Seier\Resting\Validation\Errors\RequiredValidationError;
use function Seier\Resting\Validation\Predicates\whenEquals;

class UnionResourceTest extends TestCase
{

    use AssertsErrors;

    public function testFromArrayOnUnionSubResource()
    {
        $resourceA = UnionResourceA::fromArray([
            'discriminator' => 'a',
            'a' => 'a_value',
            'value' => 'value',
        ]);

        $this->assertEquals('a_value', $resourceA->a->get());
        $this->assertEquals('value', $resourceA->value->get());
    }

    public function testUnionResourceFieldUsesResourceA()
    {
        $resourceField = new ResourceField(fn() => new UnionResourceBase);
        $resourceField->set([
            'discriminator' => 'a',
            'a' => 'a_value',
            'value' => 'value',
        ]);

        $return = $resourceField->get();

        assert($return instanceof UnionResourceA);
        $this->assertEquals('a_value', $return->a->get());
        $this->assertEquals('value', $return->value->get());
    }


    public function testUnionResourceFieldRecognizesResourceB()
    {
        $resourceField = new ResourceField(fn() => new UnionResourceBase);
        $resourceField->set([
            'discriminator' => 'b',
            'b' => 'b_value',
            'value' => 'value',
        ]);

        $return = $resourceField->get();
        assert($return instanceof UnionResourceB);
        $this->assertEquals('b_value', $return->b->get());
        $this->assertEquals('value', $return->value->get());
    }

    public function testUnionResourceFieldSetsDiscriminatorValue()
    {
        $resourceField = new ResourceField(fn() => new UnionResourceBase);
        $resourceField->set([
            'discriminator' => 'a',
            'a' => 'a_value',
            'value' => 'value',
        ]);

        $return = $resourceField->get();
        assert($return instanceof UnionResourceA);
        $this->assertEquals('a', $return->discriminator->get());
    }

    public function testToArrayReturnsValuesFromCorrectSubresource()
    {
        $unionResource = new UnionResourceBase();
        $unionResource->set($expected = [
            'discriminator' => 'a',
            'a' => 'a_value',
            'value' => 'value',
        ]);

        $this->assertEquals(
            $expected,
            $unionResource->toArray(),
        );
    }

    public function testToResponseArrayReturnsValuesFromCorrectSubresource()
    {
        $unionResource = new UnionResourceBase();
        $unionResource->set($expected = [
            'discriminator' => 'b',
            'b' => 'b_value',
            'value' => 'value',
        ]);

        $this->assertEquals(
            $expected,
            $unionResource->toResponseArray(),
        );
    }

    public function testToJsonEncodesCorrectSubresource()
    {
        $unionResource = new UnionResourceBase();
        $unionResource->set($expected = [
            'discriminator' => 'a',
            'a' => 'a_value',
            'value' => 'value',
        ]);

        $decoded = json_decode($unionResource->toJson(), JSON_OBJECT_AS_ARRAY);
        foreach ($expected as $key => $value) {
            $this->assertArrayHasKey($key, $decoded);
            $this->assertEquals($value, $decoded[$key]);
        }
    }

    public function testUnionResourceArrayField()
    {
        $arrayField = new ResourceArrayField(fn() => new UnionResourceBase());
        $arrayField->set([
            ['discriminator' => 'a', 'a' => 'a_value', 'value' => 'a_value'],
            ['discriminator' => 'b', 'b' => 'b_value', 'value' => 'b_value'],
        ]);

        $return = $arrayField->get();

        assert($return[0] instanceof UnionResourceA);
        $this->assertEquals('a_value', $return[0]->a->get());
        $this->assertEquals('a_value', $return[0]->value->get());

        assert($return[0] instanceof UnionResourceB);
        $this->assertEquals('b_value', $return[1]->b->get());
        $this->assertEquals('b_value', $return[1]->value->get());
    }

    public function testUnionSubResourceOnArrayField()
    {
        $arrayField = new ResourceArrayField(fn() => new UnionResourceA());
        $arrayField->set([
            ['discriminator' => 'a', 'a' => 'a_value', 'value' => 'a_value'],
            ['discriminator' => 'a', 'a' => 'a_value', 'value' => 'a_value'],
        ]);

        $this->assertCount(2, $return = $arrayField->get());
        $this->assertInstanceOf(UnionResourceA::class, $return[0]);
        $this->assertInstanceOf(UnionResourceA::class, $return[1]);
    }

    public function testSetFieldValidation()
    {
        $union = new UnionResourceBase();
        $exception = $this->assertThrowsValidationException(function () use ($union) {
            $union->set([
                'discriminator' => 'a',
            ]);
        });

        $this->assertCount(2, $errors = $exception->getErrors());
        $this->assertHasError($errors, RequiredValidationError::class, 'a');
        $this->assertHasError($errors, RequiredValidationError::class, 'value');
    }

    public function testSetNullableNullableFieldValidation()
    {
        $union = new UnionResourceBase();
        $exception = $this->assertThrowsValidationException(function () use ($union) {
            $union->set([
                'discriminator' => 'a',
                'a' => null,
                'value' => null,
            ]);
        });

        $this->assertCount(2, $errors = $exception->getErrors());
        $this->assertHasError($errors, NullableValidationError::class, 'a');
        $this->assertHasError($errors, NullableValidationError::class, 'value');
    }

    public function testSetRequiredFieldValidation()
    {
        $union = new UnionResourceBase();
        $exception = $this->assertThrowsValidationException(function () use ($union) {
            $union->set([]);
        });

        $this->assertCount(2, $errors = $exception->getErrors());
    }

    public function testSetPredicatedRequiredValidationWhenTrue()
    {
        $union = new UnionResourceBase();
        $union->value->required(whenEquals($union->discriminator, 'a'));

        $exception = $this->assertThrowsValidationException(function () use ($union) {
            $union->set([
                'discriminator' => 'a',
                'a' => 'a_value'
            ]);
        });

        $this->assertCount(1, $errors = $exception->getErrors());
        $this->assertHasError($errors, RequiredValidationError::class, 'value');
    }
}