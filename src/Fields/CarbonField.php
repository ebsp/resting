<?php

namespace Seier\Resting\Fields;

use Carbon\Carbon;
use Seier\Resting\Parsing\CarbonParser;
use Seier\Resting\Validation\CarbonValidator;
use Seier\Resting\Formatting\CarbonFormatter;
use Seier\Resting\Parsing\DefaultParseContext;
use Seier\Resting\Exceptions\ValidationException;
use Seier\Resting\Validation\Secondary\CarbonValidation;
use Seier\Resting\Validation\Secondary\SupportsSecondaryValidation;

class CarbonField extends Field
{
    use CarbonValidation;

    private CarbonValidator $validator;
    private CarbonParser $parser;
    private CarbonFormatter $formatter;

    public function __construct()
    {
        parent::__construct();

        $this->validator = new CarbonValidator();
        $this->parser = new CarbonParser();
        $this->formatter = new CarbonFormatter();
    }

    public function getValidator(): CarbonValidator
    {
        return $this->validator;
    }

    public function getParser(): CarbonParser
    {
        return $this->parser;
    }

    public function getFormatter(): CarbonFormatter
    {
        return $this->formatter;
    }

    public function formatted(): ?string
    {
        return $this->getFormatter()->format($this->value);
    }

    public function set($value): static
    {
        $parseContext = new DefaultParseContext($value, false);
        if ($this->parser->shouldParse($parseContext)) {
            if ($parseErrors = $this->parser->canParse($parseContext)) {
                throw new ValidationException($parseErrors);
            }

            $value = $this->parser->parse($parseContext);
        }

        return parent::set($value);
    }

    public function get(): ?Carbon
    {
        return $this->value;
    }

    public function withFormat(string $format): static
    {
        $this->withInputFormat($format);
        $this->withOutputFormat($format);

        return $this;
    }

    public function withInputFormat(string $format): static
    {
        $this->parser->withFormat($format);

        return $this;
    }

    public function withOutputFormat(string $format): static
    {
        $this->formatter->withFormat($format);

        return $this;
    }

    public function withIsoDateFormat(): static
    {
        $this->withFormat('Y-m-d|');
        $this->withOutputFormat('Y-m-d');

        return $this;
    }

    public function emptyStringAsNull(bool $state = true): static
    {
        $this->parser->emptyStringAsNull($state);
        if ($state) {
            $this->nullable();
        }

        return $this;
    }

    public function type(): array
    {
        return [
            'type' => 'string',
            'format' => 'date-time',
        ];
    }

    protected function getSupportsSecondaryValidation(): SupportsSecondaryValidation
    {
        return $this->validator;
    }
}
