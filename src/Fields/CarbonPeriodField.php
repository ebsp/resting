<?php

namespace Seier\Resting\Fields;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Seier\Resting\Parsing\CarbonParser;
use Seier\Resting\Parsing\CarbonPeriodParser;
use Seier\Resting\Parsing\DefaultParseContext;
use Seier\Resting\Exceptions\ValidationException;
use Seier\Resting\Validation\CarbonPeriodValidator;
use Seier\Resting\Validation\Errors\NotCarbonValidationError;
use Seier\Resting\Validation\Secondary\SupportsSecondaryValidation;
use Seier\Resting\Validation\Secondary\CarbonPeriod\CarbonPeriodValidation;

class CarbonPeriodField extends Field
{

    use CarbonPeriodValidation;

    private CarbonPeriodParser $parser;
    private CarbonPeriodValidator $validator;

    public function __construct()
    {
        parent::__construct();

        $this->parser = new CarbonPeriodParser();
        $this->validator = new CarbonPeriodValidator();
    }

    public function getValidator(): CarbonPeriodValidator
    {
        return $this->validator;
    }

    public function getParser(): CarbonPeriodParser
    {
        return $this->parser;
    }

    public function get(): ?CarbonPeriod
    {
        return $this->value;
    }

    public function asArray(): array
    {
        $period = $this->get();

        return [
            $period?->start,
            $period?->end,
        ];
    }

    public function start(): ?Carbon
    {
        $carbon = $this->get()?->start?->copy();

        return $carbon ? new Carbon($carbon) : null;
    }

    public function end(): ?Carbon
    {
        $carbon = $this->get()?->end?->copy();

        return $carbon ? new Carbon($carbon) : null;
    }

    public function withFormat($format): static
    {
        $apply = function (CarbonParser $validator) use ($format) {
            $validator->withFormat($format);
        };

        $this->parser->onStart($apply);
        $this->parser->onEnd($apply);

        return $this;
    }

    public function endRequired(bool $state): static
    {
        $this->validator->requireEnd($state);

        return $this;
    }

    public function endNotRequired(): static
    {
        return $this->endRequired(false);
    }

    public function set($value): static
    {
        if (is_array($value)) {
            parent::set($this->fromArray($value));
            return $this;
        }

        if ($value instanceof CarbonPeriod) {
            parent::set($value);
            return $this;
        }

        return parent::set($value);
    }

    public function type(): array
    {
        return [
            'type' => 'array',
        ];
    }

    private function fromArray(array $values): CarbonPeriod
    {

        if (count($values) && is_string($values[0])) {
            $errors = $this->parser->canParseFromArrayOfStrings(new DefaultParseContext(null, false), $values);
            if ($errors) {
                throw new ValidationException($errors);
            }

            return $this->parser->parseFromArrayOfStrings(new DefaultParseContext(null, false), $values);

        }

        $errors = [];
        $parsed = [];
        foreach ($values as $index => $value) {

            if (!$value instanceof Carbon) {
                $errors[] = (new NotCarbonValidationError($value))->prependPath($index);
                continue;
            }

            $parsed[] = $value;
        }

        if ($errors) {
            throw new ValidationException($errors);
        }

        if (count($parsed) === 0) {
            return CarbonPeriod::create($parsed[0]);
        }

        return CarbonPeriod::create($parsed[0], $parsed[1]);
    }

    protected function getSupportsSecondaryValidation(): SupportsSecondaryValidation
    {
        return $this->validator;
    }
}
