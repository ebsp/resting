<?php


namespace Seier\Resting\Tests\Meta;


use Seier\Resting\Resource;
use Seier\Resting\Fields\IntField;
use Seier\Resting\Fields\ResourceArrayField;

class ClassResource extends Resource
{

    public IntField $grade;
    public ResourceArrayField $students;

    public function __construct()
    {
        $this->grade = new IntField;
        $this->students = new ResourceArrayField(fn() => new PersonResource);
    }
}