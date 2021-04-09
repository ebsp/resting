<?php

namespace Seier\Resting\Fields;

use Seier\Resting\Parsing\Parser;
use Seier\Resting\Validation\DefaultValue;
use Seier\Resting\Validation\PrimaryValidator;
use Seier\Resting\Validation\RequiredValidator;
use Seier\Resting\Validation\NullableValidator;
use Seier\Resting\Validation\ForbiddenValidator;
use Seier\Resting\Exceptions\ValidationException;
use Seier\Resting\Validation\Predicates\Predicate;
use Seier\Resting\Validation\Secondary\SecondaryValidator;
use Seier\Resting\Validation\Errors\NullableValidationError;
use Seier\Resting\Validation\Secondary\Anonymous\AnonymousValidation;

abstract class Field
{

    use AnonymousValidation;

    protected mixed $value = null;
    protected bool $isFilled = false;
    protected bool $isEnabled = true;
    protected RequiredValidator $requiredValidator;
    protected NullableValidator $nullableValidator;
    protected ForbiddenValidator $forbiddenValidator;

    public function __construct()
    {
        $this->requiredValidator = new RequiredValidator();
        $this->nullableValidator = new NullableValidator();
        $this->forbiddenValidator = new ForbiddenValidator();
    }

    public static function create(...$arguments): static
    {
        return new static(...$arguments);
    }

    public function getValidator(): ?PrimaryValidator
    {
        return null;
    }

    public function getParser(): ?Parser
    {
        return null;
    }

    public function formatted(): mixed
    {
        return $this->value;
    }

    public function get(): mixed
    {
        return $this->value;
    }

    public function onValidator(callable $callable): static
    {
        $callable($this->getValidator());

        return $this;
    }

    public function nullable(bool|Predicate $state = true): static
    {
        if ($state instanceof Predicate) {
            $this->getNullableValidator()->setNullable(true);
            $this->getNullableValidator()->predicatedOn($state);
            return $this;
        }

        $this->getNullableValidator()->setNullable($state);

        return $this;
    }

    /**
     * Sets the default value to be used when a field was not provided (ei. that the key was not provided). The
     * default values are not evaluated or applied when null is explicitly provided.
     *
     * These default values are evaluated and applied before those registered using {@link Field::nullDefault()}.
     *
     * Multiple default values can be registered. The default values are evaluated in order of insertion. The
     * first registered default value that does not have any predicates, or that pass all their predicates is used.
     *
     * @param mixed $value The default value to be used. When provided a Closure, this closure will instead be used
     * to create the default values applied to the field.
     * @param Predicate|null $predicate Any predicates that must be true for the default value to be used.
     * @return $this
     * @throws ValidationException When the default value does not pass validation.
     */
    public function omittedDefault(mixed $value, Predicate $predicate = null): static
    {
        $this->validateValue($value);

        $defaultValue = new DefaultValue($value);
        if ($predicate !== null) {
            $defaultValue->predicatedOn($predicate);
        }

        $this->requiredValidator->withDefault($defaultValue);

        return $this;
    }

    /**
     * Sets the default value to be used when a field value is null, regardless of whether the field was not provided,
     * or if null was explicitly provided.
     *
     * These default values are evaluated and applied after those registered using {@link Field::omittedDefault()}.
     *
     * Multiple default values can be registered. The default values are evaluated in order of insertion. The
     * first registered default value that does not have any predicates, or that pass all their predicates is used.
     *
     * @param mixed $value The default value to be used. When provided a Closure, this closure will instead be used
     * to create the default values applied to the field.
     * @param Predicate|null $predicate Any predicates that must be true for the default value to be used.
     * @return $this
     * @throws ValidationException When the default value does not pass validation.
     * @see Field::omittedDefault() Another way to register default values for fields.
     */
    public function nullDefault(mixed $value, Predicate $predicate = null): static
    {
        $this->validateValue($value);

        $defaultValue = new DefaultValue($value);
        if ($predicate !== null) {
            $defaultValue->predicatedOn($predicate);
        }

        $this->nullableValidator->withDefault($defaultValue);

        return $this;
    }

    /**
     * Sets the default value to be used when a field value is null, regardless of whether the field was not provided,
     * or if null was explicitly provided. When called the field is also marked as not required.
     *
     * Multiple default values can be registered. The default values are evaluated in order of insertion. The
     * first registered default value that does not have any predicates, or that pass all their predicates is used.
     *
     * @param mixed $value The default value to be used. When provided a Closure, this closure will instead be used
     * to create the default values applied to the field.
     * @param Predicate|null $predicate Any predicates that must be true for the default value to be used.
     * @return $this
     * @throws ValidationException When the default value does not pass validation.
     * @see Field::omittedDefault() Another way to register default values for fields.
     * @see Field::nullDefault() Another way to register default values for the field.
     */
    public function withDefault(mixed $value, Predicate $predicate = null): static
    {
        $this->required(false);
        $this->nullable();
        $this->validateValue($value);
        $this->nullDefault($value, $predicate);

        return $this;
    }

    public function set($value): static
    {
        $this->validateValue($value);
        $this->value = $value;
        $this->isFilled = true;

        return $this;
    }

    private function validateValue(mixed $value)
    {
        if (is_null($value)) {

            if (!$this->getNullableValidator()->hasPredicates() && !$this->getNullableValidator()->isNullable()) {
                throw new ValidationException([
                    new NullableValidationError,
                ]);
            }

            $this->value = null;
            $this->isFilled = true;
            return;
        }

        $validator = $this->getValidator();
        if ($validator) {
            $errors = $validator->validate($value);
            if (count($errors)) {
                throw new ValidationException($errors);
            }
        }
    }

    public function notNullable(): static
    {
        $this->nullable(false);

        return $this;
    }

    public function notRequired(): static
    {
        $this->required(false);
        $this->nullable();

        return $this;
    }

    public function required(bool|Predicate $state = true): static
    {
        if ($state instanceof Predicate) {
            $this->requiredValidator->setRequired(true);
            $this->requiredValidator->predicatedOn($state);
            $this->nullableValidator->setNullable(true);
            return $this;
        }

        $this->requiredValidator->setRequired($state);
        if ($state === false) {
            $this->nullableValidator->setNullable(true);
        }

        return $this;
    }

    public function forbidden(bool|Predicate $state = true): static
    {
        if ($state instanceof Predicate) {
            $this->forbiddenValidator->setForbidden(true);
            $this->forbiddenValidator->predicatedOn($state);
            return $this;
        }

        $this->forbiddenValidator->setForbidden($state);

        return $this;
    }

    public function getRequiredValidator(): RequiredValidator
    {
        return $this->requiredValidator;
    }

    public function getNullableValidator(): NullableValidator
    {
        return $this->nullableValidator;
    }

    public function getForbiddenValidator(): ForbiddenValidator
    {
        return $this->forbiddenValidator;
    }

    public function withValidator(SecondaryValidator $secondaryValidator): static
    {
        if ($primaryValidator = $this->getValidator()) {
            $primaryValidator->withValidator($secondaryValidator);
        }

        return $this;
    }

    public function isNull(): bool
    {
        return $this->value === null;
    }

    public function isNotNull(): bool
    {
        return !$this->isNull();
    }

    public function isEmpty(): bool
    {
        return empty($this->value);
    }

    public function isNotEmpty(): bool
    {

        return !$this->isEmpty();
    }

    public function isFilled(): bool
    {
        return (bool)$this->isFilled;
    }

    public function isNotFilled(): bool
    {
        return !$this->isFilled();
    }

    public function enable(bool $state = true): static
    {
        $this->isEnabled = $state;

        return $this;
    }

    public function disable(): static
    {
        $this->isEnabled = false;

        return $this;
    }

    public function isEnabled(): bool
    {
        return $this->isEnabled;
    }

    public function isRequired(): bool
    {
        return $this->getRequiredValidator()->isRequired();
    }

    public function setFilled(bool $state = true)
    {
        $this->isFilled = $state;
    }

    abstract public function type(): array;

    public function nestedRefs(): array
    {
        return [];
    }
}
