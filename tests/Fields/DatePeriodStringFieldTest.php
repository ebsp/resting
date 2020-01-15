<?php

namespace Seier\Resting\Tests\Fields;

use Carbon\Carbon;
use Seier\Resting\Tests\TestCase;
use Seier\Resting\Rules\DatePeriodRule;
use Seier\Resting\Fields\DatePeriodStringField;
use Seier\Resting\Exceptions\InvalidPeriodException;
use Seier\Resting\Exceptions\PeriodExceedsRangeException;

class DatePeriodStringFieldTest extends TestCase
{
    public function testValidation()
    {
        $field = new DatePeriodStringField;
        $this->assertInstanceOf(DatePeriodRule::class, $field->validation()[0]);
    }

    public function testInvalidInputValidation()
    {
        $field = new DatePeriodStringField;
        $this->expectException(InvalidPeriodException::class);

        $field
            ->set(1)
            ->set([])
            ->set(null)
            ->set([null, null]);
    }

    public function testInvalidSingleValueValidation()
    {
        $field = new DatePeriodStringField;
        $this->expectException(InvalidPeriodException::class);

        $field->set('2020-00-01');
    }

    public function testInvalidValueValidation()
    {
        $field = new DatePeriodStringField;
        $this->expectException(InvalidPeriodException::class);

        $field->set('2020-01-35');
    }

    public function testValueCanBeSet()
    {
        $field = new DatePeriodStringField;
        $starts = Carbon::create(2020, 1, 1);
        $ends = Carbon::create(2020, 1, 10);
        $field->set(implode(',', [$starts->toDateString(), $ends->toDateString()]));

        $this->assertIsArray($field->get());
        $this->assertCount(2, $field->get());
        $this->assertTrue($starts->eq($field->get()[0]));
        $this->assertTrue($ends->eq($field->get()[1]));
    }

    public function testSingleValueCanBeSet()
    {
        $field = new DatePeriodStringField;
        $starts = Carbon::create(2020, 1, 1);
        $field->set($starts->toDateString());

        $this->assertIsArray($field->get());
        $this->assertCount(1, $field->get());
        $this->assertTrue($starts->eq($field->get()[0]));
    }

    public function testEndsMustBeAfterStarts()
    {
        $field = new DatePeriodStringField;
        $starts = Carbon::create(2020, 1, 10);
        $ends = Carbon::create(2020, 1, 1);

        $this->expectException(InvalidPeriodException::class);

        $field->set(implode(',', [$starts->toDateString(), $ends->toDateString()]));
    }

    public function testPeriodCannotExceedMaxRange()
    {
        $starts = Carbon::create(2020, 1, 10);
        $ends = Carbon::create(2020, 1, 1);
        $field = new DatePeriodStringField($starts->diffInDays($ends) - 1);

        $this->expectException(PeriodExceedsRangeException::class);

        $field->set(implode(',', [$starts->toDateString(), $ends->toDateString()]));
    }

    public function testEmptyReturnsNull()
    {
        $field = new DatePeriodStringField;
        $this->assertNull($field->get());
    }
}
