<?php


namespace Seier\Resting\Tests\Meta;


use Closure;
use Exception;
use Seier\Resting\Validation\PrimaryValidator;
use Seier\Resting\Validation\NullableValidator;
use Seier\Resting\Validation\Resolver\ValidatorResolver;
use Seier\Resting\Validation\Secondary\SecondaryValidator;

class MockPrimaryValidator implements PrimaryValidator
{

    private ?Closure $validator;
    private array $secondaryValidators = [];

    public function __construct(Closure $validator = null)
    {
        $this->validator = $validator;
    }

    public static function pass(): static
    {
        return new static(fn () => []);
    }

    public static function passWhenMatches(mixed $value): static
    {
        return new static(function (mixed $actual) use ($value) {
            return $actual === $value
                ? []
                : [new MockPrimaryValidationError()];
        });
    }

    public static function fail(): static
    {
        return new static(fn () => [new MockSecondaryValidationError()]);
    }

    public function getNullableValidator(): NullableValidator
    {
        $validator = new NullableValidator();
        $validator->setNullable(true);
        return $validator;
    }

    public function getValidatorResolvers(): array
    {
        throw new Exception('unsupported');
    }

    public function getSecondaryValidators(): array
    {
        return $this->secondaryValidators;
    }

    public function withValidator(SecondaryValidator $validator): static
    {
        $this->secondaryValidators[] = $validator;

        return $this;
    }

    public function withLateBoundValidator(ValidatorResolver $resolver): static
    {
        throw new Exception('unsupported');
    }

    public function description(): string
    {
        return 'mock';
    }

    public function validate(mixed $value): array
    {
        $errors = $this->validator ? ($this->validator)($value) : [];

        return array_merge($errors, $this->runSecondary($value));
    }

    public function validateThat(string $description, Closure $validator): static
    {
        throw new Exception('unsupported');
    }

    private function runSecondary(mixed $value): array
    {
        $errors = [];
        foreach ($this->secondaryValidators as $secondaryValidator) {
            $errors = array_merge($errors, $secondaryValidator->validate($value));
        }

        return $errors;
    }

    public function type(): array
    {
        return [];
    }
}