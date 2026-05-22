<?php

namespace Seier\Resting\Fields;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;
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
    private bool $useStartWhenEndIsMissing = false;
    private CarbonGranularity $granularity = CarbonGranularity::Second;

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
        $copy = $this->value?->copy();
        if ($copy && $copy->end === null && $this->useStartWhenEndIsMissing) {
            $copy = CarbonPeriod::create($copy->start->copy(), $copy->start->copy());
        }

        return $copy;
    }

    public function asArray(): array
    {
        return [
            $this->start(),
            $this->end(),
        ];
    }

    public function start(): Carbon|CarbonImmutable|null
    {
        return $this->get()?->start?->copy();
    }

    public function end(): Carbon|CarbonImmutable|null
    {
        return $this->get()?->end?->copy();
    }

    public function granularity(CarbonGranularity $granularity): static
    {
        $this->granularity = $granularity;

        return $this;
    }

    public function endRequired(bool $state): static
    {
        $this->validator->requireEnd($state);

        return $this;
    }

    public function endNotRequired(bool $useStartWhenEndIsMissing = false): static
    {
        if ($useStartWhenEndIsMissing) {
            $this->useStartWhenEndIsMissing();
        }

        return $this->endRequired(false);
    }

    public function useStartWhenEndIsMissing(): static
    {
        $this->useStartWhenEndIsMissing = true;
        $this->endNotRequired();

        return $this;
    }

    public function set($value): static
    {
        if (is_array($value)) {
            $value = $this->fromArray($value);
        }

        if ($value instanceof CarbonPeriod) {
            $value = $this->truncate($value);
        }

        return parent::set($value);
    }

    private function truncate(CarbonPeriod $period): CarbonPeriod
    {
        $start = $this->granularity->truncate($period->start->copy());

        $end = $period->end?->copy();
        if ($end !== null) {
            $end = $this->granularity->truncate($end);
        }

        return $end !== null
            ? CarbonPeriod::create($start, $end)
            : CarbonPeriod::create($start);
    }

    public function type(): array
    {
        return $this->validator->type();
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

            if (!$value instanceof CarbonInterface) {
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
