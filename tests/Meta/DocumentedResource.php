<?php

namespace Seier\Resting\Tests\Meta;

use Seier\Resting\Annotations\Doc;
use Seier\Resting\Resource;
use Seier\Resting\Fields\IntField;
use Seier\Resting\Fields\StringField;

#[Doc(self::CLASS_DOC)]
class DocumentedResource extends Resource
{
    public const CLASS_DOC = 'Curabitur pretium tincidunt lacus nulla gravida orci.';

    public const NAME_FIELD_DOC = 'Praesent sapien massa convallis a pellentesque nec.';

    public const AGE_FIELD_DOC_FIRST = 'Egestas non nisi vivamus suscipit tortor eget felis.';
    public const AGE_FIELD_DOC_SECOND = 'Porttitor mauris donec sollicitudin molestie malesuada.';

    public const STACKED_FIELD_DOC_FIRST = 'Aliquam erat volutpat morbi sit amet posuere magna.';
    public const STACKED_FIELD_DOC_SECOND = 'Vestibulum ante ipsum primis in faucibus orci luctus.';

    #[Doc(self::NAME_FIELD_DOC)]
    public StringField $name;

    #[Doc([self::AGE_FIELD_DOC_FIRST, self::AGE_FIELD_DOC_SECOND])]
    public IntField $age;

    #[Doc(self::STACKED_FIELD_DOC_FIRST)]
    #[Doc(self::STACKED_FIELD_DOC_SECOND)]
    public StringField $stackedDocs;

    public StringField $undescribed;

    public function __construct()
    {
        $this->name = new StringField;
        $this->age = new IntField;
        $this->stackedDocs = new StringField;
        $this->undescribed = new StringField;
    }
}
