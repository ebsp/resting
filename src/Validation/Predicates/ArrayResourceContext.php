<?php


namespace Seier\Resting\Validation\Predicates;


use Seier\Resting\Fields\Field;
use Seier\Resting\Parsing\DefaultParseContext;

class ArrayResourceContext implements ResourceContext
{

    private bool $isStringBased;
    private array $fields;
    private array $data;
    private array $names;

    public function __construct(array $fields, array $data, bool $isStringBased = true)
    {
        $this->fields = $fields;
        $this->data = $data;
        $this->names = [];
        $this->isStringBased = $isStringBased;

        foreach ($fields as $name => $field) {
            $this->names[$this->hash($field)] = $name;
        }
    }

    public function wasProvided(Field $field): bool
    {
        $name = $this->getName($field);

        return array_key_exists($name, $this->data);
    }

    public function isNull(Field $field): bool
    {
        $name = $this->getName($field);

        return !array_key_exists($name, $this->data) || is_null($this->data[$name]);
    }

    public function canBeParsed(Field $field): bool
    {
        $parser = $field->getParser();
        $value = $this->getRawValue($field);

        if (!is_string($value)) {
            return true;
        }

        return $parser && empty($parser->canParse(new DefaultParseContext($value, $this->isStringBased)));
    }

    public function getValue(Field $field): mixed
    {
        $value = $this->getRawValue($field);
        $parser = $field->getParser();

        if (is_string($value) && $parser) {

            return $parser->parse(new DefaultParseContext($value, $this->isStringBased));
        }

        return $value;
    }

    public function getRawValue(Field $field): mixed
    {
        $name = $this->getName($field);

        return array_key_exists($name, $this->data)
            ? $this->data[$name]
            : null;
    }

    public function getName(Field $field): string
    {
        return $this->names[$this->hash($field)];
    }

    public function getNames(Field ...$fields): array
    {
        $names = [];
        foreach ($fields as $field) {
            $names[] = $this->getName($field);
        }

        return $names;
    }

    protected function hash(Field $field): string
    {
        return spl_object_hash($field);
    }
}