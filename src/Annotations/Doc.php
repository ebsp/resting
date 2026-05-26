<?php

namespace Seier\Resting\Annotations;

use Attribute;
use ReflectionClass;
use ReflectionFunctionAbstract;
use ReflectionParameter;
use ReflectionProperty;
use Reflector;

#[Attribute(
    Attribute::TARGET_PROPERTY
    | Attribute::TARGET_METHOD
    | Attribute::TARGET_FUNCTION
    | Attribute::TARGET_PARAMETER
    | Attribute::TARGET_CLASS
    | Attribute::IS_REPEATABLE
)]
class Doc
{
    /** @var string[] */
    public readonly array $paragraphs;

    public function __construct(string|array $paragraphs)
    {
        $values = is_array($paragraphs) ? array_values($paragraphs) : [$paragraphs];

        foreach ($values as $paragraph) {
            if (!is_string($paragraph)) {
                throw new \InvalidArgumentException(
                    'Each paragraph passed to #[Doc] must be a string.'
                );
            }
        }

        $this->paragraphs = $values;
    }

    public static function paragraphsFor(Reflector $reflector): array
    {
        $paragraphs = [];

        if ($reflector instanceof ReflectionClass
            || $reflector instanceof ReflectionProperty
            || $reflector instanceof ReflectionParameter
            || $reflector instanceof ReflectionFunctionAbstract) {
            foreach ($reflector->getAttributes(self::class) as $attribute) {
                /** @var Doc $instance */
                $instance = $attribute->newInstance();
                foreach ($instance->paragraphs as $paragraph) {
                    $paragraphs[] = $paragraph;
                }
            }
        }

        return $paragraphs;
    }

    public static function descriptionFor(Reflector $reflector): ?string
    {
        $paragraphs = self::paragraphsFor($reflector);
        if (empty($paragraphs)) {
            return null;
        }

        return implode("\n\n", $paragraphs);
    }
}
