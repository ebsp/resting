<?php

namespace Seier\Resting\Tests\Meta;

use Seier\Resting\Annotations\Doc;
use Seier\Resting\Annotations\Lists;

class AnnotatedRoutes
{
    public const DESCRIBED_ENDPOINT_DOC = 'Lorem ipsum dolor sit amet.';

    public const MULTI_PARAGRAPH_DOC_FIRST = 'Consectetur adipiscing elit sed do eiusmod.';
    public const MULTI_PARAGRAPH_DOC_SECOND = 'Tempor incididunt ut labore et dolore magna aliqua.';

    public const REQUEST_BODY_PARAM_DOC = 'Ut enim ad minim veniam quis nostrud exercitation.';

    public const PATH_PARAM_DOC = 'Duis aute irure dolor in reprehenderit in voluptate.';

    public const STACKED_ENDPOINT_DOC_FIRST = 'Velit esse cillum dolore eu fugiat nulla pariatur.';
    public const STACKED_ENDPOINT_DOC_SECOND = 'Excepteur sint occaecat cupidatat non proident.';
    public const STACKED_ENDPOINT_DOC_THIRD = 'Sunt in culpa qui officia deserunt mollit anim id est laborum.';

    public const STACKED_PARAM_DOC_FIRST = 'Sed ut perspiciatis unde omnis iste natus error.';
    public const STACKED_PARAM_DOC_SECOND = 'Sit voluptatem accusantium doloremque laudantium.';

    #[Lists(UnionResourceBase::class)]
    public static function listsUnionBase(): void
    {
    }

    #[Lists(UnionParentResource::class)]
    #[Lists(UnionResourceBase::class)]
    public static function listsUnionCombination(): UnionResourceBase
    {
        return new UnionResourceA();
    }

    #[Doc(self::DESCRIBED_ENDPOINT_DOC)]
    public static function describedEndpoint(): PersonResource
    {
        return new PersonResource();
    }

    #[Doc([self::MULTI_PARAGRAPH_DOC_FIRST, self::MULTI_PARAGRAPH_DOC_SECOND])]
    public static function multiParagraphEndpoint(): PersonResource
    {
        return new PersonResource();
    }

    public static function annotatedRequestBody(
        #[Doc(self::REQUEST_BODY_PARAM_DOC)] PersonResource $person,
    ): PersonResource
    {
        return $person;
    }

    public static function pathParamEndpoint(
        #[Doc(self::PATH_PARAM_DOC)] int $id,
    ): PersonResource
    {
        return new PersonResource();
    }

    #[Lists(PersonResource::class)]
    public static function listsPersons(): void
    {
    }

    #[Doc(self::STACKED_ENDPOINT_DOC_FIRST)]
    #[Doc(self::STACKED_ENDPOINT_DOC_SECOND)]
    #[Doc(self::STACKED_ENDPOINT_DOC_THIRD)]
    public static function stackedDocEndpoint(): PersonResource
    {
        return new PersonResource();
    }

    public static function stackedDocParam(
        #[Doc(self::STACKED_PARAM_DOC_FIRST)]
        #[Doc(self::STACKED_PARAM_DOC_SECOND)]
        int $id,
    ): PersonResource
    {
        return new PersonResource();
    }
}
