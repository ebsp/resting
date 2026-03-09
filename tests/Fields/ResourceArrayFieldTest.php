<?php

namespace Seier\Resting\Tests\Fields;

use Seier\Resting\Tests\TestCase;
use Seier\Resting\Tests\Meta\Person;
use Jchook\AssertThrows\AssertThrows;
use Seier\Resting\Tests\Meta\PetResource;
use Seier\Resting\Tests\Meta\AssertsErrors;
use Seier\Resting\Fields\ResourceArrayField;
use Seier\Resting\Tests\Meta\PersonResource;
use Seier\Resting\Exceptions\ValidationException;
use Seier\Resting\Tests\Meta\MockSecondaryValidator;
use Seier\Resting\Tests\Meta\MockSecondaryValidationError;
use Seier\Resting\Validation\Errors\NullableValidationError;
use Seier\Resting\Exceptions\RestingDefinitionException;
use Seier\Resting\Tests\Meta\RequiredConstructorParamsResource;

class ResourceArrayFieldTest extends TestCase
{

    use AssertsErrors;
    use AssertThrows;

    private ResourceArrayField $instance;

    public function setUp(): void
    {
        parent::setUp();

        $this->instance = new ResourceArrayField(fn () => new PersonResource);
    }

    public function testGetEmptyReturnsNull()
    {
        $this->assertNull($this->instance->get());
    }

    public function testIsNullReturnsTrue()
    {
        $this->assertTrue($this->instance->isNull());
    }

    public function testSetWhenGivenEmptyArray()
    {
        $this->instance->set([]);

        $this->assertEquals([], $this->instance->get());
    }

    public function testSetWhenGivenArrayOfResources()
    {
        $person = new PersonResource();
        $this->instance->set([$person]);

        $this->assertCount(1, $return = $this->instance->get());
        $this->assertSame($person, $return[0]);
    }

    public function testSetWhenGivenMultidimensionalArray()
    {
        $this->instance->set([[
            'name' => $name = $this->faker->name,
            'age' => $age = $this->faker->randomNumber(2),
        ]]);

        $this->assertCount(1, $return = $this->instance->get());
        $this->assertType($return[0], function (PersonResource $resource) use ($name, $age) {
            $this->assertEquals($name, $resource->name->get());
            $this->assertEquals($age, $resource->age->get());
        });
    }

    public function testSetWhenGivenIncorrectResource()
    {
        $assertion = function (ValidationException $exception) {
            $this->assertCount(1, $exception->getErrors());
        };

        $this->assertThrows(ValidationException::class, function () {
            $this->instance->set(new PetResource());
        }, $assertion);
    }

    public function testNullableSetWhenGivenNull()
    {
        $this->instance->nullable();

        $this->instance->set(null);
        $this->assertNull($this->instance->get());
    }

    public function testNonNullableSetWhenGivenNull()
    {
        $this->instance->nullable(false);

        $this->assertThrows(ValidationException::class, function () {
            $this->instance->set(null);
        });
    }

    public function testSetResourceArrayValidation()
    {
        $this->instance->set([
            new PersonResource(),
            new PersonResource(),
        ]);

        $this->assertCount(2, $this->instance);
        $this->assertEquals(2, $this->instance->count());
    }

    public function testSetArrayValidation()
    {
        $exception = $this->assertThrowsValidationException(function () {
            $this->instance->set([
                ['name' => null, 'age' => null],
                ['name' => null, 'age' => null],
            ]);
        });

        $this->assertHasError($exception, NullableValidationError::class, '0.name');
        $this->assertHasError($exception, NullableValidationError::class, '0.age');
        $this->assertHasError($exception, NullableValidationError::class, '1.name');
        $this->assertHasError($exception, NullableValidationError::class, '1.age');
    }

    public function testValidateWithRegisteredSecondaryValidationThatPasses()
    {
        $this->instance->withValidator(MockSecondaryValidator::pass());

        $this->instance->set([]);
        $this->assertEquals([], $this->instance->get());
    }

    public function testValidateWithRegisteredSecondaryValidationThatFails()
    {
        $this->instance->withValidator(MockSecondaryValidator::fail());

        $exception = $this->assertThrowsValidationException(function () {
            $this->instance->set([]);
        });

        $this->assertHasError($exception, MockSecondaryValidationError::class);
    }

    public function testGetReturnsRawValue()
    {
        $this->instance->setRaw($raw = [
            'a' => 1,
            'b' => 2,
        ]);

        $this->assertEquals($raw, $this->instance->get());
    }

    public function testSetManyRaw()
    {
        $names = [
            $nameA = $this->faker->name,
            $nameB = $this->faker->name,
            $nameC = $this->faker->name,
        ];

        $this->instance->setManyRaw($names, function (PersonResource $resource, string $name) {
            $resource->name->set($name);
            return $resource;
        });

        $this->assertEquals([
            ['name' => $nameA],
            ['name' => $nameB],
            ['name' => $nameC],
        ], $this->instance->get());
    }

    public function testDoesNotAllowNullElementsByDefault()
    {
        $exception = $this->assertThrowsValidationException(function () {
            $this->instance->set([
                null,
                new PersonResource(),
            ]);
        });

        $this->assertHasError($exception, NullableValidationError::class, path: '0');
        $this->assertNull($this->instance->get());
        $this->assertFalse($this->instance->allowsNullElements());
    }

    public function testCanAllowNullElements()
    {
        $this->instance->allowNullElements();

        $this->instance->set([
            new PersonResource(),
            null,
            new PersonResource(),
        ]);

        $this->assertTrue($this->instance->allowsNullElements());
        $this->assertCount(3, $elements = $this->instance->get());

        $this->assertInstanceOf(PersonResource::class, $elements[0]);
        $this->assertNull($elements[1]);
        $this->assertInstanceOf(PersonResource::class, $elements[2]);
    }

    public function testAllowNullsCanDisallowNullAfterBeingAllowed()
    {
        $this->instance->allowNullElements(true);
        $this->instance->allowNullElements(false);

        $exception = $this->assertThrowsValidationException(function () {
            $this->instance->set([
                new PersonResource(),
                new PersonResource(),
                null,
            ]);
        });

        $this->assertHasError($exception, NullableValidationError::class, path: '2');
        $this->assertNull($this->instance->get());
        $this->assertFalse($this->instance->allowsNullElements());
    }

    public function testConstructorAcceptsClassName()
    {
        $field = new ResourceArrayField(PersonResource::class);

        $this->assertInstanceOf(PersonResource::class, $field->resource());
    }

    public function testConstructorWithClassNameCanSetArray()
    {
        $field = new ResourceArrayField(PersonResource::class);

        $field->set([[
            'name' => $name = $this->faker->name,
            'age' => $age = $this->faker->randomNumber(2),
        ]]);

        $this->assertCount(1, $return = $field->get());
        $this->assertType($return[0], function (PersonResource $resource) use ($name, $age) {
            $this->assertEquals($name, $resource->name->get());
            $this->assertEquals($age, $resource->age->get());
        });
    }

    public function testConstructorWithClassNameRejectsNonResourceClass()
    {
        $this->assertThrows(RestingDefinitionException::class, function () {
            new ResourceArrayField(Person::class);
        });
    }

    public function testConstructorWithClassNameRejectsRequiredConstructorParams()
    {
        $this->assertThrows(RestingDefinitionException::class, function () {
            new ResourceArrayField(RequiredConstructorParamsResource::class);
        });
    }

    public function testSetReindexesArrayWithGaps()
    {
        $personA = new PersonResource();
        $personA->name->set('A');
        $personA->age->set(1);

        $personB = new PersonResource();
        $personB->name->set('B');
        $personB->age->set(2);

        $this->instance->set([3 => $personA, 7 => $personB]);

        $result = $this->instance->get();
        $this->assertCount(2, $result);
        $this->assertSame('A', $result[0]->name->get());
        $this->assertSame('B', $result[1]->name->get());
    }

    public function testSetReindexesFilteredArray()
    {
        $personA = new PersonResource();
        $personA->name->set('A');
        $personA->age->set(10);

        $personB = new PersonResource();
        $personB->name->set('B');
        $personB->age->set(20);

        $personC = new PersonResource();
        $personC->name->set('C');
        $personC->age->set(30);

        $resources = [$personA, $personB, $personC];
        $filtered = array_filter($resources, fn (PersonResource $r) => $r->age->get() > 10);

        $this->instance->set($filtered);

        $result = $this->instance->get();
        $this->assertCount(2, $result);
        $this->assertSame('B', $result[0]->name->get());
        $this->assertSame('C', $result[1]->name->get());
    }
}
