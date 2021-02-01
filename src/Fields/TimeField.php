<?php

namespace Seier\Resting\Fields;

use Carbon\Carbon;
use Seier\Resting\Parsing\TimeParser;
use Seier\Resting\Validation\TimeValidator;
use Seier\Resting\Formatting\TimeFormatter;
use Seier\Resting\Parsing\DefaultParseContext;
use Seier\Resting\Exceptions\ValidationException;
use Seier\Resting\Validation\Secondary\TimeValidation;
use Seier\Resting\Validation\Secondary\SupportsSecondaryValidation;

class TimeField extends Field
{

    use TimeValidation;

    private TimeValidator $validator;
    private TimeParser $parser;
    private TimeFormatter $formatter;

    public function __construct()
    {
        parent::__construct();

        $this->validator = new TimeValidator();
        $this->parser = new TimeParser();
        $this->formatter = new TimeFormatter();
    }

    public function getValidator(): TimeValidator
    {
        return $this->validator;
    }

    public function getParser(): TimeParser
    {
        return $this->parser;
    }

    public function getFormatter(): TimeFormatter
    {
        return $this->formatter;
    }

    public function formatted(): ?string
    {
        return $this->getFormatter()->format($this->value);
    }

    public function withFormat(string $format): static
    {
        $this->withOutputFormat($format);

        return $this;
    }

    public function get(): ?Time
    {
        return $this->value;
    }

    public function withOutputFormat(string $format): static
    {
        $this->formatter->withFormat($format);

        return $this;
    }

    private function parsed($value): ?Time
    {
        if ($value instanceof Time) {
            return $value;
        }

        if ($value instanceof Carbon) {
            return Time::fromCarbon($value);
        }

        if (is_string($value)) {
            $parseContext = new DefaultParseContext($value, false);
            $errors = $this->parser->canParse($parseContext);
            if ($errors) {
                throw new ValidationException($errors);
            }

            return $this->parser->parse($parseContext);
        }

        return $value;
    }

    public function set($value): static
    {
        if ($value === null) {
            parent::set($value);
            return $this;
        }

        return parent::set($this->parsed($value));
    }

    public function requireSeconds(bool $state = true): static
    {
        $this->getParser()->requireSeconds($state);

        return $this;
    }

    protected function getSupportsSecondaryValidation(): SupportsSecondaryValidation
    {
        return $this->validator;
    }

    public function type(): array
    {
        return [
            'type' => 'string',
            'format' => 'time',
        ];
    }
}
