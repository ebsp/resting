<?php

namespace Seier\Resting\Tests\ResourceValidation;

use Carbon\Carbon;
use Seier\Resting\Tests\TestCase;
use Seier\Resting\ResourceValidation\ResourceAttributeComparisonValidator;

class ResourceAttributeComparisonTest extends TestCase
{

    private ResourceAttributeComparisonTestResource $resource;

    public function setUp(): void
    {
        parent::setUp();

        $this->resource = new ResourceAttributeComparisonTestResource();
    }

    public function testGreaterThanBetweenTwoFields()
    {
        $this->resource->greaterThan($this->resource->int_field_a, $this->resource->int_field_b);

        $validator = $this->getValidator();

        $this->resource->int_field_a->set(1);
        $this->resource->int_field_b->set(2);
        $this->assertNotEmpty($validator->validate());

        $this->resource->int_field_a->set(2);
        $this->resource->int_field_a->set(2);
        $this->assertNotEmpty($validator->validate());

        $this->resource->int_field_a->set(3);
        $this->resource->int_field_b->set(2);
        $this->assertEmpty($validator->validate());
    }

    public function testGreaterThanOrEqualBetweenTwoFields()
    {
        $this->resource->greaterThanOrEqual($this->resource->string_field_a, $this->resource->string_field_b);

        $validator = $this->getValidator();

        $this->resource->string_field_a->set("a");
        $this->resource->string_field_b->set("b");
        $this->assertNotEmpty($validator->validate());

        $this->resource->string_field_a->set("b");
        $this->resource->string_field_a->set("b");
        $this->assertEmpty($validator->validate());

        $this->resource->string_field_a->set("c");
        $this->resource->string_field_b->set("b");
        $this->assertEmpty($validator->validate());
    }

    public function testLessThanBetweenTwoFields()
    {
        $this->resource->lessThan($this->resource->number_field_a, $this->resource->number_field_b);

        $validator = $this->getValidator();

        $this->resource->number_field_a->set(1.1);
        $this->resource->number_field_b->set(1.2);
        $this->assertEmpty($validator->validate());

        $this->resource->number_field_a->set(1.9);
        $this->resource->number_field_b->set(2);
        $this->assertEmpty($validator->validate());

        $this->resource->number_field_a->set(2.3);
        $this->resource->number_field_b->set(2.3);
        $this->assertNotEmpty($validator->validate());

        $this->resource->number_field_a->set(5);
        $this->resource->number_field_b->set(2.3);
        $this->assertNotEmpty($validator->validate());
    }

    public function testLessThanOrEqualBetweenTwoFields()
    {
        $this->resource->lessThanOrEqual($this->resource->time_field_a, $this->resource->time_field_b);

        $validator = $this->getValidator();

        $this->resource->time_field_a->set('07:00:00');
        $this->resource->time_field_b->set('08:00:00');
        $this->assertEmpty($validator->validate());

        $this->resource->time_field_a->set('07:59:59');
        $this->resource->time_field_b->set('08:00:00');
        $this->assertEmpty($validator->validate());

        $this->resource->time_field_a->set('08:00:00');
        $this->resource->time_field_b->set('08:00:00');
        $this->assertEmpty($validator->validate());

        $this->resource->time_field_a->set('08:00:01');
        $this->resource->time_field_b->set('08:00:00');
        $this->assertNotEmpty($validator->validate());
    }

    public function testEqualBetweenTwoFields()
    {
        $this->resource->equal($this->resource->bool_field_a, $this->resource->bool_field_b);

        $validator = $this->getValidator();

        $this->resource->bool_field_a->set(true);
        $this->resource->bool_field_b->set(true);
        $this->assertEmpty($validator->validate());

        $this->resource->bool_field_a->set(false);
        $this->resource->bool_field_b->set(false);
        $this->assertEmpty($validator->validate());

        $this->resource->bool_field_a->set(true);
        $this->resource->bool_field_b->set(false);
        $this->assertNotEmpty($validator->validate());

        $this->resource->bool_field_a->set(false);
        $this->resource->bool_field_b->set(true);
        $this->assertNotEmpty($validator->validate());
    }

    public function testComparisonBetweenFieldsAndConstants()
    {
        $this->resource->greaterThan($this->resource->int_field_a, 5);

        $validator = $this->getValidator();

        $this->resource->int_field_a->set(2);
        $this->assertNotEmpty($validator->validate());

        $this->resource->int_field_a->set(5);
        $this->assertNotEmpty($validator->validate());

        $this->resource->int_field_a->set(6);
        $this->assertEmpty($validator->validate());

        $this->resource->int_field_a->set(999);
        $this->assertEmpty($validator->validate());
    }

    public function testComparisonBetweenFourFields()
    {
        $this->resource->lessThan(
            [$this->resource->date_field_a, $this->resource->time_field_a],
            [$this->resource->date_field_b, $this->resource->time_field_b],
        );

        $validator = $this->getValidator();

        $this->resource->date_field_a->set(Carbon::create(2025, 1, 1));
        $this->resource->time_field_a->set('17:00:00');
        $this->resource->date_field_b->set(Carbon::create(2025, 1, 2));
        $this->resource->time_field_b->set('05:00:00');
        $this->assertEmpty($validator->validate());

        $this->resource->date_field_a->set(Carbon::create(2025, 1, 1));
        $this->resource->time_field_a->set('05:00:00');
        $this->resource->date_field_b->set(Carbon::create(2025, 1, 1));
        $this->resource->time_field_b->set('05:00:01');
        $this->assertEmpty($validator->validate());

        $this->resource->date_field_a->set(Carbon::create(2025, 1, 1));
        $this->resource->time_field_a->set('06:00:00');
        $this->resource->date_field_b->set(Carbon::create(2025, 1, 1));
        $this->resource->time_field_b->set('06:00:00');
        $this->assertNotEmpty($validator->validate());

        $this->resource->date_field_a->set(Carbon::create(2025, 1, 2));
        $this->resource->time_field_a->set('04:00:00');
        $this->resource->date_field_b->set(Carbon::create(2025, 1, 1));
        $this->resource->time_field_b->set('05:00:00');
        $this->assertNotEmpty($validator->validate());
    }

    private function getValidator(): ResourceAttributeComparisonValidator
    {
        $validators = $this->resource->getResourceValidators();

        return $validators[count($validators) - 1];
    }
}