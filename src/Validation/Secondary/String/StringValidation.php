<?php


namespace Seier\Resting\Validation\Secondary\String;


use Seier\Resting\Validation\Secondary\SupportsSecondaryValidation;

trait StringValidation
{

    protected abstract function getSupportsSecondaryValidation(): SupportsSecondaryValidation;

    public function length(int $expectedLength): static
    {
        $this->getSupportsSecondaryValidation()->withValidator(
            new StringLengthValidator($expectedLength)
        );

        return $this;
    }

    public function notEmpty(): static
    {
        return $this->minLength(1);
    }

    public function minLength(int $minLength): static
    {
        $this->getSupportsSecondaryValidation()->withValidator(
            new StringMinLengthValidator($minLength)
        );

        return $this;
    }

    public function maxLength(int $maxLength): static
    {
        $this->getSupportsSecondaryValidation()->withValidator(
            new StringMaxLengthValidator($maxLength)
        );

        return $this;
    }

    public function betweenLength(int $minLength, int $maxLength): static
    {
        $this->minLength($minLength);
        $this->maxLength($maxLength);

        return $this;
    }

    public function matches(string $pattern): static
    {
        $this->getSupportsSecondaryValidation()->withValidator(
            new StringRegexValidator($pattern)
        );

        return $this;
    }

    public function digits(?int $length = null): static
    {
        $this->getSupportsSecondaryValidation()->withValidator(
            new StringRegexValidator('/^[0-9]+$/')
        );

        if ($length !== null) {
            $this->length($length);
        }

        return $this;
    }

    public function noWhitespace(): static
    {
        $this->getSupportsSecondaryValidation()->withValidator(
            new StringRegexValidator('/^[^\s]$/')
        );

        return $this;
    }

    public function hexColor(bool $acceptShort = true): static
    {
        $pattern = $acceptShort
            ? '/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'
            : '/^#([A-Fa-f0-9]{6})$/';

        $this->getSupportsSecondaryValidation()->withValidator(
            new StringRegexValidator($pattern)
        );

        return $this;
    }
}