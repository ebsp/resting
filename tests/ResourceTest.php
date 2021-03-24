<?php

namespace Seier\Resting\Tests;

use Carbon\Carbon;
use Seier\Resting\DynamicResource;
use Seier\Resting\Tests\Meta\Person;
use Seier\Resting\Fields\ResourceField;
use Seier\Resting\Tests\Meta\PetResource;
use Seier\Resting\Tests\Meta\ClassResource;
use Seier\Resting\Tests\Meta\EventResource;
use Seier\Resting\Tests\Meta\AssertsErrors;
use Seier\Resting\Tests\Meta\PersonResource;
use Seier\Resting\Fields\ResourceArrayField;
use Seier\Resting\Validation\Errors\NotIntValidationError;
use Seier\Resting\Validation\Errors\RequiredValidationError;
use Seier\Resting\Validation\Errors\NullableValidationError;
use Seier\Resting\Validation\Errors\NotStringValidationError;
use function Seier\Resting\Validation\Predicates\whenNotNull;

class ResourceTest extends TestCase
{

    use AssertsErrors;

    public function testSetFieldValidation()
    {
        $person = new PersonResource();
        $exception = $this->assertThrowsValidationException(function () use ($person) {
            $person->set(['name' => 1, 'age' => '']);
        });

        $this->assertCount(2, $errors = $exception->getErrors());
        $this->assertHasError($errors, NotStringValidationError::class, 'name');
        $this->assertHasError($errors, NotIntValidationError::class, 'age');
    }

    public function testSetNullableNullableFieldValidation()
    {
        $person = new PersonResource();
        $exception = $this->assertThrowsValidationException(function () use ($person) {
            $person->set(['name' => null, 'age' => null]);
        });

        $this->assertCount(2, $errors = $exception->getErrors());
        $this->assertHasError($errors, NullableValidationError::class, path: 'name');
        $this->assertHasError($errors, NullableValidationError::class, path: 'age');
    }

    public function testSetRequiredFieldValidation()
    {
        $person = new PersonResource();
        $exception = $this->assertThrowsValidationException(function () use ($person) {
            $person->set([]);
        });

        $this->assertCount(2, $errors = $exception->getErrors());
        $this->assertHasError($errors, RequiredValidationError::class, path: 'name');
        $this->assertHasError($errors, RequiredValidationError::class, path: 'age');
    }

    public function testSetPredicatedRequiredValidationWhenTrue()
    {
        $person = new PersonResource();
        $person->name->nullable();
        $person->age->required(whenNotNull($person->name));

        $exception = $this->assertThrowsValidationException(function () use ($person) {
            $person->set(['name' => $this->faker->name]);
        });

        $this->assertCount(1, $errors = $exception->getErrors());
        $this->assertHasError($errors, RequiredValidationError::class, 'age');
    }

    public function testSetPredicatedRequiredValidationWhenFalse()
    {
        $person = new PersonResource();
        $person->name->nullable();
        $person->age->required(whenNotNull($person->name));

        $person->set(['name' => null]);

        $this->assertNull($person->name->get());
        $this->assertNull($person->age->get());
    }

    public function testSetPredicatedNullableValidationWhenTrue()
    {
        $person = new PersonResource();
        $person->name->nullable();
        $person->age->nullable(whenNotNull($person->name));

        $person->set(['name' => $this->faker->name, 'age' => null]);

        $this->assertNull($person->age->get());
    }

    public function testSetPredicatedNullableValidationWhenFalse()
    {
        $person = new PersonResource();
        $person->name->nullable();
        $person->age->nullable(whenNotNull($person->name));

        $exception = $this->assertThrowsValidationException(function () use ($person) {
            $person->set(['name' => null, 'age' => null]);
        });

        $this->assertCount(1, $errors = $exception->getErrors());
        $this->assertHasError($errors, NullableValidationError::class, 'age');
    }

    public function testSetValidationRespectsOnly()
    {
        $person = new PersonResource();
        $person->only($person->name);

        $person->set(['name' => $name = $this->faker->name]);
        $this->assertEquals($name, $person->name->get());
    }

    public function testFromArray()
    {
        $resource = PersonResource::fromArray([
            'name' => $name = $this->faker->name,
            'age' => $age = $this->faker->randomNumber(2),
        ]);

        $this->assertEquals($name, $resource->name->get());
        $this->assertEquals($age, $resource->age->get());
    }

    public function testFromArrayWithResourceField()
    {
        $resource = PetResource::fromArray([
            'name' => $petName = $this->faker->name,
            'owner' => [
                'name' => $ownerName = $this->faker->name,
                'age' => $ownerAge = $this->faker->randomNumber(2),
            ]
        ]);

        $this->assertEquals($petName, $resource->name->get());
        $this->assertEquals($ownerName, $resource->getOwner()->name->get());
        $this->assertEquals($ownerAge, $resource->getOwner()->age->get());
    }

    public function testFromArrayWithResourceArrayField()
    {
        $resource = ClassResource::fromArray([
            'grade' => $grade = $this->faker->randomNumber(2),
            'students' => [
                [
                    'name' => $studentNameA = $this->faker->name,
                    'age' => $studentAgeA = $this->faker->randomNumber(2),
                ],
                [
                    'name' => $studentNameB = $this->faker->name,
                    'age' => $studentAgeB = $this->faker->randomNumber(2),
                ],
            ]
        ]);

        $this->assertEquals($grade, $resource->grade->get());
        $this->assertEquals($studentNameA, $resource->students[0]->name->get());
        $this->assertEquals($studentAgeA, $resource->students[0]->age->get());
        $this->assertEquals($studentNameB, $resource->students[1]->name->get());
        $this->assertEquals($studentAgeB, $resource->students[1]->age->get());
    }

    public function testFromArrayCanThrowValidationException()
    {
        $exception = $this->assertThrowsValidationException(function () {
            PersonResource::fromArray(['name' => null, 'age' => null]);
        });

        $this->assertHasError($exception, NullableValidationError::class, 'name');
        $this->assertHasError($exception, NullableValidationError::class, 'age');
    }

    public function testFromCollection()
    {
        $resource = PersonResource::fromCollection(collect([
            'name' => $name = $this->faker->name,
            'age' => $age = $this->faker->randomNumber(2),
        ]));

        $this->assertEquals($name, $resource->name->get());
        $this->assertEquals($age, $resource->age->get());
    }

    public function testFromCollectionCanThrowValidationException()
    {
        $exception = $this->assertThrowsValidationException(function () {
            PersonResource::fromCollection(collect(['name' => null, 'age' => null]));
        });

        $this->assertHasError($exception, NullableValidationError::class, 'name');
        $this->assertHasError($exception, NullableValidationError::class, 'age');
    }

    public function testMapManyAcceptsArray()
    {
        $persons = [];
        $resource = new PersonResource();
        $result = $resource->mapMany($persons, function (PersonResource $resource, Person $person) {
            return $resource->from($person);
        });

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testMapManyAcceptsCollection()
    {
        $persons = collect();
        $resource = new PersonResource();
        $result = $resource->mapMany($persons, function (PersonResource $resource, Person $person) {
            return $resource->from($person);
        });

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testMapMany()
    {
        $persons = [
            Person::from('A', 1),
            Person::from('C', 2),
            Person::from('B', 5),
        ];

        $resource = new PersonResource();
        $result = $resource->mapMany($persons, function (PersonResource $resource, Person $person) {
            return $resource->from($person);
        });

        $this->assertIsArray($result);
        $this->assertEquals([
            ['name' => 'A', 'age' => 1],
            ['name' => 'C', 'age' => 2],
            ['name' => 'B', 'age' => 5],
        ], $result);
    }

    public function testToArray()
    {
        $resource = new PersonResource();
        $resource->name->set($name = $this->faker->name);
        $resource->age->set($age = $this->faker->randomNumber(2));

        $this->assertEquals(
            ['name' => $name, 'age' => $age],
            $resource->toArray()
        );
    }

    public function testToArrayRespectsOnly()
    {
        $resource = new PersonResource();
        $resource->name->set($name = $this->faker->name);
        $resource->age->set($this->faker->randomNumber(2));

        $resource->only($resource->name);

        $this->assertEquals(
            ['name' => $name],
            $resource->toArray(),
        );
    }

    public function testToArrayWithResourceField()
    {
        $owner = new PersonResource();
        $owner->name->set($ownerName = $this->faker->name);
        $owner->age->set($ownerAge = $this->faker->randomNumber(2));

        $pet = new PetResource();
        $pet->name->set($petName = $this->faker->name);
        $pet->owner->set($owner);

        $this->assertEquals(
            ['name' => $petName, 'owner' => ['name' => $ownerName, 'age' => $ownerAge]],
            $pet->toArray()
        );
    }

    public function testToArrayWithResourceArrayField()
    {
        $expected = [
            'grade' => $this->faker->randomNumber(1),
            'students' => [
                ['name' => $this->faker->name, 'age' => $this->faker->randomNumber(2)],
                ['name' => $this->faker->name, 'age' => $this->faker->randomNumber(2)],
                ['name' => $this->faker->name, 'age' => $this->faker->randomNumber(2)],
            ],
        ];

        $petResource = ClassResource::fromArray($expected);
        $this->assertSame($expected, $petResource->toArray());
    }

    public function testToArrayDoesNotRemoveNulls()
    {
        $resource = new PersonResource();
        $expected = ['name' => null, 'age' => null];

        $this->assertEquals($expected, $resource->toArray());
    }

    public function testToArrayDoesNotRemoveEmptyArrays()
    {
        $resource = new ClassResource();
        $resource->students->set([]);

        $expected = ['grade' => null, 'students' => []];

        $this->assertEquals($expected, $resource->toArray());
    }

    public function testToArrayDoesNotFormatValues()
    {
        $resource = new EventResource();
        $resource->name->set($name = $this->faker->word);
        $resource->time->set($time = now());

        $array = $resource->toArray();

        $this->assertEquals($name, $array['name']);
        $this->assertInstanceOf(Carbon::class, $array['time']);
        $this->assertEquals($time->unix(), $array['time']->unix());
    }

    public function testToArrayReturnsRawWhenSet()
    {
        $resource = new PersonResource();
        $resource->name->set($this->faker->name);
        $resource->age->set($this->faker->randomNumber(2));

        $resource->setRaw($raw = [1, 2, 3]);
        $this->assertEquals($raw, $resource->toArray());
    }

    public function testToResponseArray()
    {
        $resource = new PersonResource();
        $resource->name->set($name = $this->faker->name);
        $resource->age->set($age = $this->faker->randomNumber(2));

        $this->assertEquals(
            ['name' => $name, 'age' => $age],
            $resource->toResponseArray()
        );
    }

    public function testToResponseArrayRespectsOnly()
    {
        $resource = new PersonResource();
        $resource->name->set($name = $this->faker->name);
        $resource->age->set($this->faker->randomNumber(2));

        $resource->only($resource->name);

        $this->assertEquals(
            ['name' => $name],
            $resource->toResponseArray(),
        );
    }

    public function testToResponseArrayRemovesNullsWhenSet()
    {
        $resource = new PetResource();
        $resource->removeNulls(true);

        $this->assertCount(0, $resource->toResponseArray());
    }

    public function testToResponseArrayDoesNotRemoveNullsWhenNotSet()
    {
        $resource = new PetResource();
        $resource->removeNulls(false);

        $this->assertCount(2, $response = $resource->toResponseArray());
        $this->assertNull($response['name']);
        $this->assertNull($response['owner']);
    }

    public function testToResponseArrayRemovesEmptyArraysWhenSet()
    {
        $resource = new ClassResource();
        $resource->students->set([]);
        $resource->removeEmptyArrays(true);

        $this->assertEquals([], $resource->toResponseArray());
    }

    public function testToResponseArrayRemovesEmptyArrayWhenNotSet()
    {
        $resource = new ClassResource();
        $resource->students->set([]);
        $resource->removeEmptyArrays(false);

        $expected = ['students' => []];
        $this->assertEquals($expected, $resource->toResponseArray());
    }

    public function testToResponseArrayReturnsRawWhenSet()
    {
        $resource = new PersonResource();
        $resource->name->set($name = $this->faker->name);
        $resource->age->set($age = $this->faker->randomNumber(2));

        $resource->setRaw($raw = [1, 2, 3]);
        $this->assertEquals($raw, $resource->toResponseArray());
    }

    public function testToResponseArrayFormatsValues()
    {
        $resource = new EventResource();
        $resource->name->set($name = $this->faker->word);
        $resource->time->set($time = now());

        $array = $resource->toResponseArray();

        $this->assertEquals(
            ['name' => $name, 'time' => $resource->time->getFormatter()->format($time)],
            $array,
        );
    }

    public function testToResponseArrayFormatsResourceFields()
    {
        $event = new EventResource();
        $event->name->set($name = $this->faker->name);
        $event->time->set($time = now());

        $resource = new DynamicResource();
        $resource->withField('event', (new ResourceField(fn() => new EventResource))->set($event));

        $response = $resource->toResponseArray();
        $expected = ['event' => [
            'name' => $name,
            'time' => $event->time->getFormatter()->format($time),
        ]];

        $this->assertSame($expected, $response);
    }

    public function testToResponseArrayFormatsResourceArrayFields()
    {
        $event = new EventResource();
        $event->name->set($name = $this->faker->name);
        $event->time->set($time = now());

        $resource = new DynamicResource();
        $resource->withField('events', (new ResourceArrayField(fn() => new EventResource))->set([$event]));

        $response = $resource->toResponseArray();
        $expected = ['events' => [[
            'name' => $name,
            'time' => $event->time->getFormatter()->format($time),
        ]]];

        $this->assertSame($expected, $response);
    }

    public function testToJsonEncodesValues()
    {
        $resource = new PersonResource();
        $resource->name->set($name = $this->faker->name);
        $resource->age->set($age = $this->faker->randomNumber(2));

        $this->assertEquals(
            ['name' => $name, 'age' => $age],
            json_decode($resource->toJson(), JSON_OBJECT_AS_ARRAY),
        );
    }

    public function testToJsonRespectsOnly()
    {
        $resource = new PersonResource();
        $resource->name->set($name = $this->faker->name);
        $resource->age->set($this->faker->randomNumber(2));

        $resource->only($resource->name);

        $this->assertEquals(
            json_encode(['name' => $name]),
            $resource->toJson(),
        );
    }

    public function testToJsonEncodesRawWhenSet()
    {
        $resource = new PersonResource();
        $resource->name->set($name = $this->faker->name);
        $resource->age->set($age = $this->faker->randomNumber(2));

        $resource->setRaw($raw = [1, 2, 3]);
        $this->assertEquals(json_encode($raw), $resource->toJson());
    }

    public function testToJsonRemovesNullsWhenSet()
    {
        $resource = new PersonResource();
        $resource->age->nullable();
        $resource->name->set($name = $this->faker->name);

        $resource->removeNulls(true);

        $this->assertEquals(
            json_encode(['name' => $name]),
            $resource->toJson()
        );
    }

    public function testToJsonDoesNotRemoveNullsWhenNotSet()
    {
        $resource = new PersonResource();
        $resource->name->set($name = $this->faker->name);
        $resource->age->nullable();
        $resource->age->set(null);

        $resource->removeNulls(false);

        $this->assertEquals(
            json_encode(['name' => $name, 'age' => null]),
            $resource->toJson()
        );
    }

    public function testToJsonRemovesEmptyArraysWhenSet()
    {
        $resource = new ClassResource();
        $resource->students->set([]);
        $resource->removeEmptyArrays(true);

        $this->assertEquals(
            json_encode([]),
            $resource->toJson()
        );
    }

    public function testToJsonRemovesEmptyArrayWhenNotSet()
    {
        $resource = new ClassResource();
        $resource->students->set([]);
        $resource->removeEmptyArrays(false);

        $this->assertEquals(
            json_encode(['students' => []]),
            $resource->toJson()
        );
    }

    public function testFromRawDoesNotPerformValidation()
    {
        $resource = PersonResource::fromRaw($expected = [1, 2, 3]);

        $this->assertEquals($expected, $resource->toArray());
        $this->assertEquals($expected, $resource->toResponseArray());
    }
}
