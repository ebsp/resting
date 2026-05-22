<?php

namespace Seier\Resting\Fields;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
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
    private CarbonGranularity $granularity = CarbonGranularity::Second;

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

        if ($value instanceof CarbonInterface) {
            $value = $this->granularity->truncate($value->copy());
        }

        return parent::set($value);
    }

    public function get(): Carbon|CarbonImmutable|null
    {
        return $this->value?->copy();
    }

    public function granularity(CarbonGranularity $granularity): static
    {
        $this->granularity = $granularity;
        $this->formatter->withGranularity($granularity);

        return $this;
    }

    public function withFormat(string $format): static
    {
        $this->formatter->withFormat($format);

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
        return $this->validator->type();
    }

    protected function getSupportsSecondaryValidation(): SupportsSecondaryValidation
    {
        return $this->validator;
    }
}
