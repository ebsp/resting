<?php

namespace Seier\Resting\Fields;

use ArrayAccess;
use ReflectionEnum;
use Seier\Resting\Parsing\Parser;
use Seier\Resting\Parsing\IntParser;
use Seier\Resting\Parsing\BoolParser;
use Seier\Resting\Parsing\TimeParser;
use Seier\Resting\Parsing\EnumParser;
use Seier\Resting\Parsing\ArrayParser;
use Seier\Resting\Parsing\StringParser;
use Seier\Resting\Parsing\CarbonParser;
use Seier\Resting\Parsing\NumberParser;
use Seier\Resting\Validation\IntValidator;
use Illuminate\Contracts\Support\Arrayable;
use Seier\Resting\Validation\BoolValidator;
use Seier\Resting\Validation\TimeValidator;
use Seier\Resting\Validation\EnumValidator;
use Seier\Resting\Validation\ArrayValidator;
use Seier\Resting\Validation\StringValidator;
use Seier\Resting\Validation\CarbonValidator;
use Seier\Resting\Validation\NumberValidator;
use Seier\Resting\Validation\PrimaryValidator;
use Seier\Resting\Validation\Secondary\Arrays\ArrayValidation;
use Seier\Resting\Validation\Secondary\SupportsSecondaryValidation;

class ArrayField extends Field
{

    use ArrayValidation;

    private ArrayValidator $validator;
    private ArrayParser $parser;

    public function __construct()
    {
        parent::__construct();

        $this->validator = new ArrayValidator();
        $this->parser = new ArrayParser();
    }

    public function get(): ?array
    {
        return parent::get();
    }

    public function getValidator(): ArrayValidator
    {
        return $this->validator;
    }

    public function getParser(): ArrayParser
    {
        return $this->parser;
    }

    public function set($value): static
    {
        if ($value instanceof Arrayable) {
            $value = $value->toArray();
        }

        if ($value instanceof ArrayAccess) {
            $value = [...$value];
        }

        return parent::set($value);
    }

    public function ofStrings(callable $config = null): static
    {
        $validator = new StringValidator();
        $parser = new StringParser();

        if ($config) {
            $config($validator, $parser);
        }

        return $this->of($validator, $parser);
    }

    public function ofIntegers(callable $config = null): static
    {
        $validator = new IntValidator();
        $parser = new IntParser();

        if ($config) {
            $config($validator, $parser);
        }

        return $this->of($validator, $parser);
    }

    public function ofNumbers(callable $config = null): static
    {
        $validator = new NumberValidator();
        $parser = new NumberParser();

        if ($config) {
            $config($validator, $parser);
        }

        return $this->of($validator, $parser);
    }

    public function ofBooleans(callable $config = null): static
    {
        $validator = new BoolValidator();
        $parser = new BoolParser();

        if ($config) {
            $config($validator, $parser);
        }

        return $this->of($validator, $parser);
    }

    public function ofTimes(callable $config = null): static
    {
        $validator = new TimeValidator();
        $parser = new TimeParser();

        if ($config) {
            $config($validator, $parser);
        }

        return $this->of($validator, $parser);
    }

    public function ofArrays(callable $config = null): static
    {
        $validator = new ArrayValidator();
        $parser = new ArrayParser();

        if ($config) {
            $config($validator, $parser);
        }

        return $this->of($validator, $parser);
    }

    public function ofCarbons(callable $config = null): static
    {
        $validator = new CarbonValidator();
        $parser = new CarbonParser();

        if ($config) {
            $config($validator, $parser);
        }

        return $this->of($validator, $parser);
    }

    public function ofEnums(string|ReflectionEnum $enumType): static
    {
        if (is_string($enumType)) {
            $enumType = new ReflectionEnum($enumType);
        }

        $validator = new EnumValidator($enumType);
        $parser = new EnumParser($enumType);

        return $this->of($validator, $parser);
    }

    public function of(PrimaryValidator $validator, Parser $parser): static
    {
        $this->setElementValidator($validator);
        $this->setElementParser($parser);

        return $this;
    }

    public function setElementValidator(PrimaryValidator $validator): static
    {
        $this->validator->setElementValidator($validator);

        return $this;
    }

    public function setElementParser(Parser $parser): static
    {
        $this->parser->setElementParser($parser);

        return $this;
    }

    protected function getSupportsSecondaryValidation(): SupportsSecondaryValidation
    {
        return $this->validator;
    }

    public function type(): array
    {
        return [
            'type' => 'array',
            'items' => [],
        ];
    }
}
