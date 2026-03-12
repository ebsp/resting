<?php

namespace Seier\Resting\Tests\Meta;

use Seier\Resting\Resource;
use Seier\Resting\Fields\ArrayField;

class ArrayFieldsResource extends Resource
{
    public ArrayField $with_strings;
    public ArrayField $with_integers;
    public ArrayField $with_enums;
    public ArrayField $with_booleans;

    public ArrayField $with_nullable_strings;
    public ArrayField $with_nullable_integers;
    public ArrayField $with_nullable_enums;
    public ArrayField $with_nullable_booleans;

    public function __construct()
    {
        $this->with_strings = (new ArrayField)->ofStrings();
        $this->with_integers = (new ArrayField)->ofIntegers();
        $this->with_enums = (new ArrayField)->ofEnums(SuiteEnum::class);
        $this->with_booleans = (new ArrayField)->ofBooleans();

        $this->with_nullable_strings = (new ArrayField)->ofStrings()->allowNullElements();
        $this->with_nullable_integers = (new ArrayField)->ofIntegers()->allowNullElements();
        $this->with_nullable_enums = (new ArrayField)->ofEnums(SuiteEnum::class)->allowNullElements();
        $this->with_nullable_booleans = (new ArrayField)->ofBooleans()->allowNullElements();
    }
}