<?php

namespace Seier\Resting\Fields;

use ArrayAccess;
use Seier\Resting\Parsing\Parser;
use Seier\Resting\Parsing\IntParser;
use Seier\Resting\Parsing\BoolParser;
use Seier\Resting\Parsing\TimeParser;
use Seier\Resting\Parsing\ArrayParser;
use Seier\Resting\Parsing\StringParser;
use Seier\Resting\Parsing\CarbonParser;
use Seier\Resting\Parsing\NumberParser;
use Seier\Resting\Validation\IntValidator;
use Illuminate\Contracts\Support\Arrayable;
use Seier\Resting\Validation\BoolValidator;
use Seier\Resting\Validation\TimeValidator;
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

    /**
     * Add insert validator of strings only on set/insert
     *
     * @param callable|null $config
     * @return static
     */
    public function ofStrings(?callable $config = null): static
    {
        $validator = new StringValidator();
        $parser = new StringParser();

        if ($config) {
            $config($validator, $parser);
        }

        return $this->of($validator, $parser);
    }

    /**
     * Add insert validator of integers only on set/insert
     *
     * @param callable|null $config
     * @return static
     */
    public function ofIntegers(?callable $config = null): static
    {
        $validator = new IntValidator();
        $parser = new IntParser();

        if ($config) {
            $config($validator, $parser);
        }

        return $this->of($validator, $parser);
    }

    /**
     * Add insert validator of numbers only on set/insert
     *
     * @param callable|null $config
     * @return static
     */
    public function ofNumbers(?callable $config = null): static
    {
        $validator = new NumberValidator();
        $parser = new NumberParser();

        if ($config) {
            $config($validator, $parser);
        }

        return $this->of($validator, $parser);
    }

    /**
     * Add insert validator of booleans only on set/insert
     *
     * @param callable|null $config
     * @return static
     */
    public function ofBooleans(?callable $config = null): static
    {
        $validator = new BoolValidator();
        $parser = new BoolParser();

        if ($config) {
            $config($validator, $parser);
        }

        return $this->of($validator, $parser);
    }

    /**
     * Add insert validator of times only on set/insert \
     * Seier\Resting\Fields\Time
     *
     * @param callable|null $config
     * @return static
     */
    public function ofTimes(?callable $config = null): static
    {
        $validator = new TimeValidator();
        $parser = new TimeParser();

        if ($config) {
            $config($validator, $parser);
        }

        return $this->of($validator, $parser);
    }

    /**
     * Add insert validator of arrays only on set/insert
     *
     * @param callable|null $config
     * @return static
     */
    public function ofArrays(?callable $config = null): static
    {
        $validator = new ArrayValidator();
        $parser = new ArrayParser();

        if ($config) {
            $config($validator, $parser);
        }

        return $this->of($validator, $parser);
    }

    /**
     * Add insert validator of Carbon's only on set/insert
     *
     * @param callable|null $config
     * @return static
     * @link https://packagist.org/packages/nesbot/carbon
     */
    public function ofCarbons(?callable $config = null): static
    {
        $validator = new CarbonValidator();
        $parser = new CarbonParser();

        if ($config) {
            $config($validator, $parser);
        }

        return $this->of($validator, $parser);
    }

    /**
     * Structure custom Validator and Parser for array contents
     *
     * @param PrimaryValidator $validator
     * @param Parser $parser
     * @return static
     */
    public function of(PrimaryValidator $validator, Parser $parser): static
    {
        $this->setElementValidator($validator);
        $this->setElementParser($parser);

        return $this;
    }

    /**
     * Change ElementValidator on the fly
     *
     * @param PrimaryValidator $validator
     * @return static
     */
    public function setElementValidator(PrimaryValidator $validator): static
    {
        $this->validator->setElementValidator($validator);

        return $this;
    }

    /**
     * Change the Parser on the fly
     *
     * @param Parser $parser
     * @return static
     */
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
