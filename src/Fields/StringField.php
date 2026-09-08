<?php

namespace Seier\Resting\Fields;

use Seier\Resting\Parsing\Parser;
use Seier\Resting\Parsing\StringParser;
use Seier\Resting\Validation\StringValidator;
use Seier\Resting\Parsing\DefaultParseContext;
use Seier\Resting\Exceptions\ValidationException;
use Seier\Resting\Validation\Secondary\In\InValidation;
use Seier\Resting\Validation\Secondary\String\StringValidation;
use Seier\Resting\Validation\Secondary\SupportsSecondaryValidation;

class StringField extends Field
{

    use StringValidation;
    use InValidation;

    private StringValidator $validator;
    private StringParser $parser;
    private array $transformers;

    public function __construct()
    {
        parent::__construct();

        $this->validator = new StringValidator();
        $this->parser = new StringParser();
        $this->transformers = [];
    }

    public function transform(callable $callable): static
    {
        $this->transformers[] = $callable;

        return $this;
    }

    public function upper(): static
    {
        $this->transformers[] = mb_strtoupper(...);

        return $this;
    }

    public function lower(): static
    {
        $this->transformers[] = mb_strtolower(...);

        return $this;
    }

    public function stripWhitespace(): static
    {
        $this->transformers[] = static function (string $value) {
            return preg_replace('/\s+/', '', $value);
        };

        return $this;
    }

    public function getValidator(): StringValidator
    {
        return $this->validator;
    }

    public function getParser(): ?Parser
    {
        return $this->parser;
    }

    public function set($value): static
    {
        if (is_string($value)) {
            foreach ($this->transformers as $mapper) {
                $value = $mapper($value);
            }
        }

        $parseContext = new DefaultParseContext($value, false);
        if ($this->parser->shouldParse($parseContext)) {
            $errors = $this->parser->canParse($parseContext);
            if ($errors) {
                throw new ValidationException($errors);
            }

            $value = $this->parser->parse($parseContext);
        }

        return parent::set($value);
    }

    public function get(): ?string
    {
        return $this->value;
    }

    public function getNotEmpty(bool $trim = false): ?string
    {
        $value = $this->value;
        if ($trim && is_string($value)) {
            $value = trim($value);
        }

        return empty($value)
            ? null
            : $value;
    }

    public function emptyStringAsNull(bool $state = true): static
    {
        $this->parser->emptyStringAsNull($state);

        return $this;
    }

    /**
     * Instructs the field whether or not to trim the value before it is validated and stored. Values are
     * trimmed by default, meaning that both the value returned from {@link StringField::get()} and the
     * value seen by validators is trimmed. Call trim(false) to disable trimming.
     *
     * @param bool $state Whether or not the value should be trimmed.
     * @return $this
     */
    public function trim(bool $state = true): static
    {
        $this->parser->trim($state);

        return $this;
    }

    public function isTrimmed(): bool
    {
        return $this->parser->isTrimmed();
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
