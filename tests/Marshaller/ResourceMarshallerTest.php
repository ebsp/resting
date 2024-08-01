<?php


namespace Seier\Resting\Tests\Marshaller;


use Seier\Resting\Tests\TestCase;
use Seier\Resting\DynamicResource;
use Seier\Resting\Fields\RawField;
use Seier\Resting\Fields\TimeField;
use Seier\Resting\Fields\CarbonField;
use Seier\Resting\Fields\StringField;
use Seier\Resting\Tests\Meta\PetResource;
use Seier\Resting\Tests\Meta\ClassResource;
use Seier\Resting\Tests\Meta\AssertsErrors;
use Seier\Resting\Tests\Meta\PersonResource;
use Seier\Resting\Tests\Meta\UnionResourceA;
use Seier\Resting\Tests\Meta\UnionResourceB;
use Seier\Resting\Tests\Meta\ActivityResource;
use Seier\Resting\Tests\Meta\UnionResourceBase;
use Seier\Resting\Marshaller\ResourceMarshaller;
use Seier\Resting\Tests\Meta\UnionParentResource;
use Seier\Resting\Validation\Errors\NotIntValidationError;
use Seier\Resting\Validation\Errors\NotArrayValidationError;
use Seier\Resting\Validation\Errors\RequiredValidationError;
use Seier\Resting\Validation\Errors\NullableValidationError;
use Seier\Resting\Validation\Errors\NotStringValidationError;
use Seier\Resting\Validation\Errors\ForbiddenValidationError;
use Seier\Resting\Validation\Secondary\Comparable\MinValidationError;
use Seier\Resting\Validation\Errors\UnknownUnionDiscriminatorValidationError;
use function Seier\Resting\Validation\Predicates\whenNull;
use function Seier\Resting\Validation\Predicates\whenEquals;
use function Seier\Resting\Validation\Predicates\whenProvided;

class ResourceMarshallerTest extends TestCase
{

    use AssertsErrors;

    private ResourceMarshaller $instance;

    public function setUp(): void
    {
        parent::setUp();

        $this->instance = new ResourceMarshaller();
    }

    public function testMarshalResource()
    {
        $factory = $this->resourceFactory(PersonResource::class);
        $result = $this->instance->marshalResource($factory, [
            'name' => $name = $this->faker->name,
            'age' => $age = $this->faker->randomNumber(2),
        ]);

        $this->assertFalse($result->hasErrors());
        $this->assertType($result->getValue(), function (PersonResource $person) use ($age, $name) {
            $this->assertEquals($name, $person->name->get());
            $this->assertEquals($age, $person->age->get());
        });
    }

    public function testMarshalResourceDoesNotSetDisabledFields()
    {
        $factory = $this->resourceFactory(function () {
            $resource = new PersonResource();
            $resource->age->disable();
            return $resource;
        });

        $result = $this->instance->marshalResource($factory, [
            'name' => $name = $this->faker->name,
            'age' => $this->faker->randomNumber(2),
        ]);

        $this->assertFalse($result->hasErrors());
        $this->assertType($result->getValue(), function (PersonResource $person) use ($name) {
            $this->assertEquals($name, $person->name->get());
            $this->assertNull($person->age->get());
            $this->assertFalse($person->age->isFilled());
        });
    }

    public function testMarshalNullableResourceWhenNull()
    {
        $factory = $this->resourceFactory(PersonResource::class);
        $result = $this->instance->marshalNullableResource($factory, null);

        $this->assertFalse($result->hasErrors());
        $this->assertNull($result->getValue());
    }

    public function testMarshalNullableResourceWhenNotNull()
    {
        $factory = $this->resourceFactory(PersonResource::class);
        $result = $this->instance->marshalNullableResource($factory, [
            'name' => $name = $this->faker->name,
            'age' => $age = $this->faker->randomNumber(2),
        ]);

        $this->assertFalse($result->hasErrors());
        $this->assertType($result->getValue(), function (PersonResource $person) use ($age, $name) {
            $this->assertEquals($name, $person->name->get());
            $this->assertEquals($age, $person->age->get());
        });
    }

    public function testMarshalResourceWithPredicatedRequiredThatIsTrue()
    {
        $factory = $this->resourceFactory(function () {
            $person = new PersonResource();
            $person->name->required(whenProvided($person->age));
            $person->age->notRequired();
            return $person;
        });

        $result = $this->instance->marshalResource($factory, [
            'age' => $this->faker->randomNumber(2),
        ]);

        $this->assertCount(1, $errors = $result->getErrors());
        $this->assertHasError($errors, RequiredValidationError::class, 'name');
    }

    public function testMarshalResourceWithPredicatedRequiredThatIsFalse()
    {
        $factory = $this->resourceFactory(function () {
            $person = new PersonResource();
            $person->name->required(whenProvided($person->age))->nullable();
            $person->age->notRequired();
            return $person;
        });

        $result = $this->instance->marshalResource($factory, []);

        $this->assertFalse($result->hasErrors());
        $this->assertType($result->getValue(), function (PersonResource $person) {
            $this->assertNull($person->name->get());
            $this->assertNull($person->age->get());
        });
    }

    public function testMarshalResourceWithPredicatedForbiddenThatIsTrue()
    {
        $factory = $this->resourceFactory(function () {
            $person = new PersonResource();
            $person->name->forbidden(whenProvided($person->age));
            $person->age->notRequired();
            return $person;
        });

        $result = $this->instance->marshalResource($factory, [
            'name' => $this->faker->name,
            'age' => $this->faker->randomNumber(2),
        ]);

        $this->assertCount(1, $errors = $result->getErrors());
        $this->assertHasError($errors, ForbiddenValidationError::class, 'name');
    }

    public function testMarshalResourceWithPredicatedForbiddenThatIsFalse()
    {
        $factory = $this->resourceFactory(function () {
            $person = new PersonResource();
            $person->name->forbidden(whenProvided($person->age));
            $person->age->notRequired();
            return $person;
        });

        $result = $this->instance->marshalResource($factory, [
            'name' => $name = $this->faker->name,
        ]);

        $this->assertFalse($result->hasErrors());
        $this->assertType($result->getValue(), function (PersonResource $person) use ($name) {
            $this->assertEquals($name, $person->name->get());
            $this->assertNull($person->age->get());
        });
    }

    public function testMarshalResourceWithPredicatedNullableThatIsTrue()
    {
        $factory = $this->resourceFactory(function () {
            $person = new PersonResource();
            $person->name->nullable(whenNull($person->age));
            $person->age->nullable();
            return $person;
        });

        $result = $this->instance->marshalResource($factory, [
            'name' => null,
            'age' => null,
        ]);

        $this->assertFalse($result->hasErrors());
        $this->assertType($result->getValue(), function (PersonResource $person) {
            $this->assertNull($person->name->get());
            $this->assertNull($person->age->get());
        });
    }

    public function testMarshalResourceWithPredicatedNullableThatIsFalse()
    {
        $factory = $this->resourceFactory(function () {
            $person = new PersonResource();
            $person->name->nullable(whenNull($person->age));
            $person->age->nullable();
            return $person;
        });

        $result = $this->instance->marshalResource($factory, [
            'name' => null,
            'age' => $this->faker->randomNumber(2),
        ]);

        $this->assertCount(1, $errors = $result->getErrors());
        $this->assertHasError($errors, NullableValidationError::class, 'name');
    }

    public function testMarshalResourceWithLateValidationCanHandleNullValue()
    {
        $factory = $this->resourceFactory(function () {
            $activity = new ActivityResource();
            $activity->end->after($activity->start)->nullable();
            return $activity;
        });

        $result = $this->instance->marshalResource($factory, [
            'start' => $start = now(),
            'end' => null,
        ]);

        $this->assertFalse($result->hasErrors());
        $this->assertType($result->getValue(), function (ActivityResource $activity) use ($start) {
            $this->assertEquals($activity->start->get(), $start);
            $this->assertNull($activity->end->get());
        });
    }

    public function testMarshalResourceWithLateValidationThatPasses()
    {
        $factory = $this->resourceFactory(function () {
            $activity = new ActivityResource();
            $activity->end->after($activity->start);
            return $activity;
        });

        $result = $this->instance->marshalResource($factory, [
            'start' => $start = now(),
            'end' => $end = $start->copy()->addSecond(),
        ]);

        $this->assertFalse($result->hasErrors());
        $this->assertType($result->getValue(), function (ActivityResource $activity) use ($end, $start) {
            $this->assertEquals($activity->start->get(), $start);
            $this->assertEquals($activity->end->get(), $end);
        });
    }

    public function testMarshalResourceWithLateValidationThatFails()
    {
        $factory = $this->resourceFactory(function () {
            $activity = new ActivityResource();
            $activity->end->after($activity->start);
            return $activity;
        });

        $result = $this->instance->marshalResource($factory, [
            'start' => $start = now(),
            'end' => $end = $start->copy(),
        ]);

        $this->assertCount(1, $errors = $result->getErrors());
        $this->assertHasError($errors, MinValidationError::class, 'end');
    }

    public function testMarshalResourceField()
    {
        $factory = $this->resourceFactory(PetResource::class);
        $result = $this->instance->marshalResource($factory, [
            'name' => $petName = $this->faker->name,
            'owner' => [
                'name' => $ownerName = $this->faker->name,
                'age' => $ownerAge = $this->faker->randomNumber(2),
            ]
        ]);

        $this->assertFalse($result->hasErrors());
        $this->assertType($result->getValue(), function (PetResource $pet) use ($ownerAge, $ownerName, $petName) {
            $this->assertEquals($petName, $pet->name->get());
            $this->assertEquals($ownerName, $pet->getOwner()->name->get());
            $this->assertEquals($ownerAge, $pet->getOwner()->age->get());
        });
    }

    public function testMarshalResourceArrayField()
    {
        $factory = $this->resourceFactory(ClassResource::class);
        $result = $this->instance->marshalResource($factory, [
            'grade' => $grade = $this->faker->randomNumber(1),
            'students' => [
                ['name' => $nameA = $this->faker->name, 'age' => $ageA = $this->faker->randomNumber(2)],
                ['name' => $nameB = $this->faker->name, 'age' => $ageB = $this->faker->randomNumber(2)],
            ],
        ]);

        $this->assertFalse($result->hasErrors());
        $this->assertType($result->getValue(), function (ClassResource $class) use ($nameA, $ageA, $nameB, $ageB) {
            $this->assertCount(2, $students = $class->students);
            $this->assertEquals($nameA, $students[0]->name->get());
            $this->assertEquals($ageA, $students[0]->age->get());
            $this->assertEquals($nameB, $students[1]->name->get());
            $this->assertEquals($ageB, $students[1]->age->get());
        });
    }

    public function testMarshalUnionResource()
    {
        $factory = $this->resourceFactory(UnionResourceBase::class);
        $result = $this->instance->marshalResource($factory, [
            'discriminator' => $discriminator = 'b',
            'value' => $value = $this->faker->word,
            'b' => $b = $this->faker->word,
        ]);

        $this->assertFalse($result->hasErrors());
        $this->assertType($result->getValue(), function (UnionResourceB $resource) use ($b, $result, $discriminator, $value) {
            $this->assertEquals($discriminator, $resource->discriminator->get());
            $this->assertEquals($value, $resource->value->get());
            $this->assertEquals($b, $resource->b->get());
        });
    }

    public function testMarshalResourceWithUnionResource()
    {
        $factory = $this->resourceFactory(UnionParentResource::class);
        $result = $this->instance->marshalResource($factory, [
            'other' => $other = $this->faker->word,
            'union' => [
                'discriminator' => 'b',
                'b' => $b = $this->faker->word,
                'value' => $value = $this->faker->word,
            ]
        ]);

        $this->assertFalse($result->hasErrors());
        $this->assertType($result->getValue(), function (UnionParentResource $parent) use ($b, $value, $other) {
            $this->assertEquals($other, $parent->other->get());
            $this->assertType($parent->union->get(), function (UnionResourceB $resource) use ($b, $value) {
                $this->assertEquals('b', $resource->discriminator->get());
                $this->assertEquals($value, $resource->value->get());
                $this->assertEquals($b, $resource->b->get());
            });
        });
    }

    public function testMarshalResourceArray()
    {
        $factory = $this->resourceFactory(PersonResource::class);
        $result = $this->instance->marshalResources($factory, [
            ['name' => $nameA = $this->faker->name, 'age' => $ageA = $this->faker->randomNumber(2)],
            ['name' => $nameB = $this->faker->name, 'age' => $ageB = $this->faker->randomNumber(2)],
        ]);

        $this->assertFalse($result->hasErrors());
        $this->assertCount(2, $persons = $result->getValue());

        $this->assertEquals($nameA, $persons[0]->name->get());
        $this->assertEquals($ageA, $persons[0]->age->get());
        $this->assertEquals($nameB, $persons[1]->name->get());
        $this->assertEquals($ageB, $persons[1]->age->get());
    }

    public function testMarshalResourceArrayEmpty()
    {
        $factory = $this->resourceFactory(PersonResource::class);
        $result = $this->instance->marshalResources($factory, []);

        $this->assertFalse($result->hasErrors());
        $this->assertIsArray($result->getValue());
        $this->assertCount(0, $result->getValue());
    }

    public function testMarshalUnionResourceArray()
    {
        $factory = $this->resourceFactory(UnionResourceBase::class);
        $result = $this->instance->marshalResources($factory, [
            ['discriminator' => 'a', 'a' => $a = $this->faker->word, 'value' => $valueA = $this->faker->word],
            ['discriminator' => 'b', 'b' => $b = $this->faker->word, 'value' => $valueB = $this->faker->word],
        ]);

        $this->assertFalse($result->hasErrors());
        $this->assertCount(2, $result = $result->getValue());

        $this->assertInstanceOf(UnionResourceA::class, $resourceA = $result[0]);
        $this->assertEquals($valueA, $resourceA->value->get());
        $this->assertEquals($a, $resourceA->a->get());
        $this->assertInstanceOf(UnionResourceB::class, $resourceB = $result[1]);
        $this->assertEquals($valueB, $resourceB->value->get());
        $this->assertEquals($b, $resourceB->b->get());
    }

    public function testMarshalResourceArrayWithUnionResource()
    {
        $factory = $this->resourceFactory(UnionParentResource::class);
        $result = $this->instance->marshalResources($factory, [
            [
                'other' => $otherA = $this->faker->word,
                'union' => ['discriminator' => 'a', 'a' => $a = $this->faker->word, 'value' => $valueA = $this->faker->word],
            ],
            [
                'other' => $otherB = $this->faker->word,
                'union' => ['discriminator' => 'b', 'b' => $b = $this->faker->word, 'value' => $valueB = $this->faker->word],
            ]
        ]);

        $this->assertFalse($result->hasErrors());
        $this->assertCount(2, $result = $result->getValue());

        $this->assertInstanceOf(UnionParentResource::class, $parentA = $result[0]);
        $this->assertEquals($otherA, $parentA->other->get());
        $this->assertInstanceOf(UnionResourceA::class, $unionA = $parentA->union->get());
        $this->assertEquals($valueA, $unionA->value->get());
        $this->assertEquals($a, $unionA->a->get());

        $this->assertInstanceOf(UnionParentResource::class, $parentB = $result[1]);
        $this->assertEquals($otherB, $parentB->other->get());
        $this->assertInstanceOf(UnionResourceB::class, $unionB = $parentB->union->get());
        $this->assertEquals($valueB, $unionB->value->get());
        $this->assertEquals($b, $unionB->b->get());
    }

    public function testMarshalResourceArrayWithResourceField()
    {
        $factory = $this->resourceFactory(ClassResource::class);
        $result = $this->instance->marshalResources($factory, [[
            'grade' => $grade = $this->faker->randomNumber(1),
            'students' => [
                ['name' => $nameA = $this->faker->name, 'age' => $ageA = $this->faker->randomNumber(2)],
                ['name' => $nameB = $this->faker->name, 'age' => $ageB = $this->faker->randomNumber(2)],
            ],
        ]]);

        $this->assertFalse($result->hasErrors());
        $this->assertCount(1, $result = $result->getValue());

        $this->assertInstanceOf(ClassResource::class, $class = $result[0]);
        $this->assertEquals($grade, $class->grade->get());
        $this->assertCount(2, $students = $class->students);

        $this->assertInstanceOf(PersonResource::class, $personA = $students[0]);
        $this->assertEquals($nameA, $personA->name->get());
        $this->assertEquals($ageA, $personA->age->get());

        $this->assertInstanceOf(PersonResource::class, $personB = $students[1]);
        $this->assertEquals($nameB, $personB->name->get());
        $this->assertEquals($ageB, $personB->age->get());
    }

    public function testMarshalResourceArrayWhenProvidedJsonObject()
    {
        $factory = $this->resourceFactory(PersonResource::class);
        $result = $this->instance->marshalResources($factory, [
            'name' => $this->faker->name,
            'age' => $this->faker->randomNumber(2),
        ]);

        $this->assertCount(1, $errors = $result->getErrors());
        $this->assertHasError($errors, NotArrayValidationError::class);
    }

    public function testMarshalResourceArrayFieldWhenProvidedJsonObject()
    {
        $factory = $this->resourceFactory(ClassResource::class);
        $result = $this->instance->marshalResource($factory, [
            'grade' => $this->faker->randomNumber(1),
            'students' => [
                'name' => $this->faker->name,
                'age' => $this->faker->randomNumber(2),
            ],
        ]);

        $this->assertCount(1, $errors = $result->getErrors());
        $this->assertHasError($errors, NotArrayValidationError::class, 'students');
    }

    public function testRequiredValidationOnFields()
    {
        $factory = $this->resourceFactory(PersonResource::class);
        $result = $this->instance->marshalResource($factory, [
            'age' => 0,
        ]);

        $this->assertCount(1, $errors = $result->getErrors());
        $this->assertHasError($errors, RequiredValidationError::class, 'name');
    }

    public function testRequiredValidationOnNestedFields()
    {
        $factory = $this->resourceFactory(PetResource::class);
        $result = $this->instance->marshalResource($factory, [
            'name' => $this->faker->name,
            'owner' => [
                'name' => $this->faker->name,
            ]
        ]);

        $this->assertCount(1, $errors = $result->getErrors());
        $this->assertHasError($errors, RequiredValidationError::class, 'owner.age');
    }

    public function testRequiredValidationOnResourceArrayField()
    {
        $factory = $this->resourceFactory(ClassResource::class);
        $result = $this->instance->marshalResource($factory, [
            'grade' => $this->faker->numberBetween(0, 9),
            'students' => [[
                'name' => $this->faker->name,
            ]]
        ]);

        $this->assertCount(1, $errors = $result->getErrors());
        $this->assertHasError($errors, RequiredValidationError::class, 'students.0.age');
    }

    public function testRequiredValidationOnManyResourcesInResourceArrayField()
    {
        $factory = $this->resourceFactory(ClassResource::class);
        $result = $this->instance->marshalResource($factory, [
            'grade' => $this->faker->numberBetween(0, 9),
            'students' => [
                ['name' => $this->faker->name],
                ['age' => $this->faker->randomNumber(2)],
            ]
        ]);

        $this->assertCount(2, $errors = $result->getErrors());
        $this->assertHasError($errors, RequiredValidationError::class, 'students.0.age');
        $this->assertHasError($errors, RequiredValidationError::class, 'students.1.name');
    }

    public function testRequiredValidationManyOnSameResourceInResourceArrayField()
    {
        $factory = $this->resourceFactory(ClassResource::class);
        $result = $this->instance->marshalResource($factory, [
            'grade' => $this->faker->numberBetween(0, 9),
            'students' => [
                [],
            ]
        ]);

        $this->assertCount(2, $errors = $result->getErrors());
        $this->assertHasError($errors, RequiredValidationError::class, 'students.0.name');
        $this->assertHasError($errors, RequiredValidationError::class, 'students.0.age');
    }

    public function testForbiddenValidationOnFields()
    {
        $factory = $this->resourceFactory(function () {
            $person = new PersonResource();
            $person->name->forbidden();
            $person->age->notRequired();
            return $person;
        });

        $result = $this->instance->marshalResource($factory, [
            'name' => $this->faker->name,
        ]);

        $this->assertCount(1, $errors = $result->getErrors());
        $this->assertHasError($errors, ForbiddenValidationError::class, 'name');
    }

    public function testForbiddenValidationOnNestedFields()
    {
        $factory = $this->resourceFactory(function () {
            return tap(new PetResource, function (PetResource $petResource) {
                $petResource->owner->setResourcePrototypeFactory(function () {
                    $person = new PersonResource();
                    $person->name->forbidden();
                    $person->age->notRequired();
                    return $person;
                });
            });
        });

        $result = $this->instance->marshalResource($factory, [
            'name' => $this->faker->name,
            'owner' => [
                'name' => $this->faker->name,
            ]
        ]);

        $this->assertCount(1, $errors = $result->getErrors());
        $this->assertHasError($errors, ForbiddenValidationError::class, 'owner.name');
    }

    public function testForbiddenValidationOnResourceArrayField()
    {
        $factory = $this->resourceFactory(function () {
            return tap(new ClassResource, function (ClassResource $classResource) {
                $classResource->students->setResourcePrototypeFactory(function () {
                    $person = new PersonResource();
                    $person->name->forbidden();
                    $person->age->notRequired();
                    return $person;
                });
            });
        });

        $result = $this->instance->marshalResource($factory, [
            'grade' => $this->faker->numberBetween(0, 9),
            'students' => [[
                'name' => $this->faker->name,
            ]]
        ]);

        $this->assertCount(1, $errors = $result->getErrors());
        $this->assertHasError($errors, ForbiddenValidationError::class, 'students.0.name');
    }

    public function testForbiddenValidationOnManyResourcesInResourceArrayField()
    {
        $factory = $this->resourceFactory(function () {
            return tap(new ClassResource, function (ClassResource $classResource) {
                $classResource->students->setResourcePrototypeFactory(function () {
                    $person = new PersonResource();
                    $person->name->forbidden();
                    $person->age->notRequired();
                    return $person;
                });
            });
        });

        $result = $this->instance->marshalResource($factory, [
            'grade' => $this->faker->numberBetween(0, 9),
            'students' => [
                ['name' => $this->faker->name],
                ['name' => $this->faker->name],
            ]
        ]);

        $this->assertCount(2, $errors = $result->getErrors());
        $this->assertHasError($errors, ForbiddenValidationError::class, 'students.0.name');
        $this->assertHasError($errors, ForbiddenValidationError::class, 'students.1.name');
    }

    public function testForbiddenValidationManyOnSameResourceInResourceArrayField()
    {
        $factory = $this->resourceFactory(function () {
            return tap(new ClassResource, function (ClassResource $classResource) {
                $classResource->students->setResourcePrototypeFactory(function () {
                    $person = new PersonResource();
                    $person->name->forbidden();
                    $person->age->forbidden();
                    return $person;
                });
            });
        });

        $result = $this->instance->marshalResource($factory, [
            'grade' => $this->faker->numberBetween(0, 9),
            'students' => [
                ['name' => $this->faker->name, 'age' => $this->faker->randomNumber(2)],
            ]
        ]);

        $this->assertCount(2, $errors = $result->getErrors());
        $this->assertHasError($errors, ForbiddenValidationError::class, 'students.0.name');
        $this->assertHasError($errors, ForbiddenValidationError::class, 'students.0.age');
    }

    public function testNullableWhenProvidedNull()
    {
        $factory = $this->personNullable();
        $result = $this->instance->marshalResource($factory, [
            'name' => null,
            'age' => null,
        ]);

        $this->assertFalse($result->hasErrors());
        $this->assertType($result->getValue(), function (PersonResource $person) {
            $this->assertTrue($person->name->isFilled());
            $this->assertTrue($person->age->isFilled());
        });
    }

    public function testNullableWhenProvidedValue()
    {
        $factory = $this->personNullable();
        $result = $this->instance->marshalResource($factory, [
            'name' => $name = $this->faker->name,
            'age' => $age = $this->faker->randomNumber(2),
        ]);

        $this->assertFalse($result->hasErrors());
        $this->assertType($result->getValue(), function (PersonResource $person) use ($name, $age) {
            $this->assertEquals($name, $person->name->get());
            $this->assertEquals($age, $person->age->get());
        });
    }

    public function testNullableValidationWhenNotProvided()
    {
        $factory = $this->personNullable();
        $result = $this->instance->marshalResource($factory, []);

        $this->assertCount(1, $result->getErrorsForPath('name'));
        $this->assertCount(1, $result->getErrorsForPath('age'));

        $this->assertType($result->getValue(), function (PersonResource $person) {
            $this->assertFalse($person->name->isFilled());
            $this->assertFalse($person->age->isFilled());
            $this->assertNull($person->name->get());
            $this->assertNull($person->age->get());
        });
    }

    public function testNullableResourceField()
    {
        $factory = $this->petWithNullableOwner();
        $result = $this->instance->marshalResource($factory, [
            'name' => $this->faker->name,
            'owner' => null,
        ]);

        $this->assertFalse($result->hasErrors());
        $this->assertType($result->getValue(), function (PetResource $pet) {
            $this->assertTrue($pet->owner->isFilled());
            $this->assertNull($pet->owner->get());
        });
    }

    public function testValidationWhenUnionResourceDiscriminatorIsMissing()
    {
        $result = $this->instance->marshalResource($this->resourceFactory(UnionResourceBase::class), [
            'value' => 'value'
        ]);

        $this->assertCount(1, $errors = $result->getErrors());
        $this->assertHasError($result->getErrors(), RequiredValidationError::class, 'discriminator');
    }

    public function testValidationWhenUnionResourceDiscriminatorIsUnknown()
    {
        $result = $this->instance->marshalResource($this->resourceFactory(UnionResourceBase::class), [
            'discriminator' => 'unknown'
        ]);

        $this->assertCount(1, $errors = $result->getErrors());
        $this->assertHasError($result->getErrors(), UnknownUnionDiscriminatorValidationError::class, 'discriminator');
    }

    public function testValidationWhenFieldValidationFails()
    {
        $result = $this->instance->marshalResource($this->resourceFactory(PersonResource::class), [
            'name' => 0,
            'age' => 1,
        ]);

        $this->assertCount(1, $errors = $result->getErrors());
        $this->assertHasError($result->getErrors(), NotStringValidationError::class, 'name');
    }

    public function testValidationWhenThereAreManyErrorsOnSameResource()
    {
        $result = $this->instance->marshalResource($this->resourceFactory(PersonResource::class), [
            'name' => 0,
            'age' => '',
        ]);

        $this->assertCount(2, $errors = $result->getErrors());
        $this->assertHasError($errors, NotStringValidationError::class, 'name');
        $this->assertHasError($errors, NotIntValidationError::class, 'age');
    }

    public function testValidationOnNestedResourceFields()
    {
        $result = $this->instance->marshalResource($this->resourceFactory(PetResource::class), [
            'name' => $this->faker->name,
            'owner' => [
                'name' => 0,
                'age' => $this->faker->randomNumber(2),
            ],
        ]);

        $this->assertCount(1, $errors = $result->getErrors());
        $this->assertHasError($errors, NotStringValidationError::class, 'owner.name');
    }

    public function testValidationWhenRootResourceProvidedString()
    {
        $result = $this->instance->marshalResource($this->resourceFactory(PersonResource::class), '');

        $this->assertCount(1, $errors = $result->getErrors());
        $this->assertHasError($errors, NotArrayValidationError::class);
    }

    public function testValidationWhenRootResourceProvidedInteger()
    {
        $result = $this->instance->marshalResource($this->resourceFactory(PersonResource::class), 0);

        $this->assertCount(1, $errors = $result->getErrors());
        $this->assertHasError($errors, NotArrayValidationError::class);
    }

    public function testMarshalResourceFieldSetsFilledTrueWhenProvided()
    {
        $factory = $this->resourceFactory(function () {
            $person = new PersonResource();
            $person->name->required(false);
            $person->age->required(false);
            return $person;
        });

        $result = $this->instance->marshalResource($factory, [
            'name' => $this->faker->name,
            'age' => $this->faker->randomNumber(2)
        ]);

        $resource = $result->getValue();
        assert($resource instanceof PersonResource);
        $this->assertTrue($resource->name->isFilled());
        $this->assertTrue($resource->age->isFilled());
    }

    public function testMarshalResourceFieldSetsFilledTrueWhenProvidedNull()
    {
        $factory = $this->resourceFactory(function () {
            $person = new PersonResource();
            $person->name->required(false);
            $person->age->required(false);
            return $person;
        });

        $result = $this->instance->marshalResource($factory, [
            'name' => null,
            'age' => null
        ]);

        $resource = $result->getValue();
        assert($resource instanceof PersonResource);
        $this->assertTrue($resource->name->isFilled());
        $this->assertTrue($resource->age->isFilled());
    }

    public function testMarshalResourceFieldSetsFilledFalseWhenNotProvided()
    {
        $factory = $this->resourceFactory(function () {
            $person = new PersonResource();
            $person->name->notRequired();
            $person->age->notRequired();
            return $person;
        });

        $result = $this->instance->marshalResource($factory, []);

        $resource = $result->getValue();
        assert($resource instanceof PersonResource);
        $this->assertFalse($resource->name->isFilled());
        $this->assertFalse($resource->age->isFilled());
    }

    public function testMarshalFieldUsesOmittedDefaultValue()
    {
        $factory = $this->resourceFactory(function () {
            $person = new PersonResource();
            $person->name->notRequired();
            $person->age->notRequired()->omittedDefault(5);
            return $person;
        });

        $result = $this->instance->marshalResource($factory, []);

        $this->assertFalse($result->hasErrors());
        $resource = $result->getValue();
        assert($resource instanceof PersonResource);
        $this->assertNull($resource->name->get());
        $this->assertEquals(5, $resource->age->get());
    }

    public function testMarshalFieldDoesNotUseOmittedDefaultValueWhenProvidedNull()
    {
        $factory = $this->resourceFactory(function () {
            $person = new PersonResource();
            $person->name->notRequired();
            $person->age->notRequired()->omittedDefault(5);
            return $person;
        });

        $result = $this->instance->marshalResource($factory, ['age' => null]);

        $this->assertFalse($result->hasErrors());
        $resource = $result->getValue();
        assert($resource instanceof PersonResource);
        $this->assertNull($resource->name->get());
        $this->assertNull($resource->age->get());
    }

    public function testMarshalFieldUsesNullDefaultValueWhenOmitted()
    {
        $factory = $this->resourceFactory(function () {
            $person = new PersonResource();
            $person->name->notRequired();
            $person->age->notRequired()->nullDefault(5);
            return $person;
        });

        $result = $this->instance->marshalResource($factory, []);

        $this->assertFalse($result->hasErrors());
        $resource = $result->getValue();
        assert($resource instanceof PersonResource);
        $this->assertNull($resource->name->get());
        $this->assertEquals(5, $resource->age->get());
    }

    public function testMarshalFieldUsesNullDefaultValueWhenProvidedNull()
    {
        $factory = $this->resourceFactory(function () {
            $person = new PersonResource();
            $person->name->notRequired();
            $person->age->notRequired()->nullDefault(5);
            return $person;
        });

        $result = $this->instance->marshalResource($factory, ['age' => null]);

        $this->assertFalse($result->hasErrors());
        $resource = $result->getValue();
        assert($resource instanceof PersonResource);
        $this->assertNull($resource->name->get());
        $this->assertEquals(5, $resource->age->get());
    }

    public function testMarshalFieldEvaluatesPredicatedOmittedDefault()
    {
        $factory = $this->resourceFactory(function () {
            $person = new PersonResource();
            $person->age->notRequired();
            $person->age->omittedDefault(2, whenEquals($person->name, '.'));
            $person->age->omittedDefault(3, whenEquals($person->name, '..'));
            return $person;
        });

        $result = $this->instance->marshalResource($factory, ['name' => '..']);

        $this->assertFalse($result->hasErrors());
        $resource = $result->getValue();
        assert($resource instanceof PersonResource);
        $this->assertEquals(3, $resource->age->get());
    }

    public function testMarshalFieldEvaluatesPredicatedNullDefault()
    {
        $factory = $this->resourceFactory(function () {
            $person = new PersonResource();
            $person->age->notRequired();
            $person->age->nullDefault(2, whenEquals($person->name, '.'));
            $person->age->nullDefault(3, whenEquals($person->name, '..'));
            return $person;
        });

        $result = $this->instance->marshalResource($factory, ['name' => '..']);

        $this->assertFalse($result->hasErrors());
        $resource = $result->getValue();
        assert($resource instanceof PersonResource);
        $this->assertEquals(3, $resource->age->get());
    }

    public function testMarshalResourceFieldWithResourceAsDefault()
    {
        $factory = $this->resourceFactory(function () {
            $pet = new PetResource();
            $pet->name->notRequired();
            $pet->owner->notRequired()->resourceAsDefault();
            return $pet;
        });

        $result = $this->instance->marshalResource($factory, []);

        $this->assertFalse($result->hasErrors());
        $resource = $result->getValue();
        assert($resource instanceof PetResource);
        $this->assertNull($resource->getOwner()->name->get());
        $this->assertNull($resource->getOwner()->age->get());
    }

    public function testCanParseIntegersWhenAllowsParsing()
    {
        $this->instance->isStringBased();

        $factory = $this->resourceFactory(PersonResource::class);
        $result = $this->instance->marshalResource($factory, [
            'name' => $name = $this->faker->name,
            'age' => $age = '1',
        ]);

        $this->assertFalse($result->hasErrors());
        $this->assertType($result->getValue(), function (PersonResource $person) use ($name, $age) {
            $this->assertEquals($name, $person->name->get());
            $this->assertEquals(intval($age), $person->age->get());
        });
    }

    public function testFieldEmptyStringAsNull()
    {
        $factory = $this->resourceFactory(function () {
            $dynamic = new DynamicResource();

            $dynamic->withField('time', (new TimeField)->emptyStringAsNull());
            $dynamic->withField('carbon', (new CarbonField)->emptyStringAsNull());
            $dynamic->withField('string', (new StringField)->emptyStringAsNull());

            return $dynamic;
        });

        $result = $this->instance->marshalResource($factory, [
            'time' => '',
            'carbon' => '',
            'string' => '',
        ]);

        $this->assertFalse($result->hasErrors());
        $this->assertType($result->getValue(), function (DynamicResource $resource) {

            $this->assertTrue($resource->time->isFilled());
            $this->assertNull($resource->time->get());

            $this->assertTrue($resource->carbon->isFilled());
            $this->assertNull($resource->carbon->get());

            $this->assertTrue($resource->string->isFilled());
            $this->assertNull($resource->string->get());

        });
    }

    public function testRawFieldWhenProvidedArray()
    {
        $factory = $this->resourceFactory(function () {
            $dynamic = new DynamicResource();
            $dynamic->withField('raw_array', (new RawField));
            $dynamic->withField('raw_int', (new RawField));
            $dynamic->withField('raw_string', (new RawField));
            $dynamic->withField('raw_float', (new RawField));
            $dynamic->withField('raw_false', (new RawField));
            $dynamic->withField('raw_true', (new RawField));
            return $dynamic;
        });

        $result = $this->instance->marshalResource($factory, [
            'raw_array' => [],
            'raw_int' => 1,
            'raw_string' => "raw",
            'raw_float' => 1.2,
            'raw_false' => false,
            'raw_true' => true,
        ]);

        $this->assertFalse($result->hasErrors());
        $this->assertType($result->getValue(), function (DynamicResource $resource) {

            $this->assertTrue($resource->raw_array->isFilled());
            $this->assertSame([], $resource->raw_array->get());

            $this->assertTrue($resource->raw_int->isFilled());
            $this->assertSame(1, $resource->raw_int->get());

            $this->assertTrue($resource->raw_string->isFilled());
            $this->assertSame('raw', $resource->raw_string->get());

            $this->assertTrue($resource->raw_float->isFilled());
            $this->assertSame(1.2, $resource->raw_float->get());

            $this->assertTrue($resource->raw_false->isFilled());
            $this->assertFalse($resource->raw_false->get());

            $this->assertTrue($resource->raw_true->isFilled());
            $this->assertTrue($resource->raw_true->get());

        });
    }
}