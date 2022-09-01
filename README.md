# Resting

Helper classes to improve type awareness/restrictions on your REST API in Laravel.
The goal is to fail faster when receiving unexpected parameter types with helpful information in the callback.

[![Packagist PHP Version Support](https://img.shields.io/packagist/php-v/ebsp/resting?color=blue&style=for-the-badge)](https://www.php.net/releases/)
[![GitHub issues](https://img.shields.io/github/issues-raw/ebsp/resting?color=red&style=for-the-badge)](https://github.com/AlexWestergaard/php-ga4/issues)
[![Packagist Stars](https://img.shields.io/packagist/stars/ebsp/resting?color=yellow&style=for-the-badge)](https://github.com/AlexWestergaard/php-ga4/stargazers)

## **Notice**
This codebase is currently developed during personal usage.
Please be vary of using this in production as breaking changes can happen.

## Resources
Resources are classes representing the data you want to send and receive in your REST API.
Resources are intermediate data layers for your REST API Interface; making it more flexible.
A resource is made up of Fields (Type restricted roperties).
The Field is type aware of the parameter and validation criteria(s).

Resources must extend `Seier\Resting\Resource` to use this package fully as intended.

### Field Types
The Resting package comes with a number of predefined field types. The following field types are included:

[![Field](https://shields.io/badge/Field-blue)](src/Fields/Field.php)
[![StringField](https://shields.io/badge/StringField-blue)](src/Fields/StringField.php)
[![NumberField](https://shields.io/badge/NumberField-blue)](src/Fields/NumberField.php)
[![IntField](https://shields.io/badge/IntField-blue)](src/Fields/IntField.php)
[![BoolField](https://shields.io/badge/BoolField-blue)](src/Fields/BoolField.php)
[![ArrayField](https://shields.io/badge/ArrayField-blue)](src/Fields/ArrayField.php)
[![DateField](https://shields.io/badge/DateField-blue)](src/Fields/DateField.php)
[![EnumField](https://shields.io/badge/EnumField-blue)](src/Fields/EnumField.php)
[![CarbonField](https://shields.io/badge/CarbonField-blue)](src/Fields/CarbonField.php)

[![PasswordField](https://shields.io/badge/PasswordField-blue)](src/Fields/PasswordField.php)
[![ResourceField](https://shields.io/badge/ResourceField-blue)](src/Fields/ResourceField.php)
[![ResourceArrayField](https://shields.io/badge/ResourceArrayField-blue)](src/Fields/ResourceArrayField.php)

### Field Class Layout

```php
namespace Seier\Resting\Fields;

abstract class Field
{
    // Default Values
    protected mixed $value = null;
    protected bool $isFilled = false;
    protected bool $isEnabled = true;

    // statically call "new class"
    public static function new(): static;

    // Returns the current validator if set
    public function getValidator(): ?Seier\Resting\Validation\PrimaryValidator;
    public function getRequiredValidator(): RequiredValidator;
    public function getNullableValidator(): NullableValidator;
    public function getForbiddenValidator(): ForbiddenValidator;
    public function withValidator(SecondaryValidator $secondaryValidator): static;

    // Returns the current parser if set
    public function getParser(): ?Parser;

    // Returns formatted value based on Field model (child)
    // Type should be specified in child
    public function formatted(): mixed;

    // Returns the raw value as stored
    // Type should be specified in child
    public function get(): mixed;

    // Define if value of class should be nullable
    // If predicate is passed that will be set as true
    public function nullable(bool|Predicate $state = true): static;
    public function notNullable(): static;

    // Read the full description inside class of following
    public function omittedDefault(mixed $value, Predicate $predicate = null): static;
    public function nullDefault(mixed $value, Predicate $predicate = null): static;
    public function withDefault(mixed $value, Predicate $predicate = null): static;

    // Updates the raw value with validation
    // Expected Type should be specified in child
    public function set($value): static;

    // Define if value is required
    public function required(bool|Predicate $state = true): static;
    public function notRequired(): static;

    // Decide wether this value is allowed in serialization
    public function enable(bool $state = true): static;
    public function disable(): static;

    // Specify if the variable have been set
    public function setFilled(bool $state = true);

    // Check if current value is NULL
    public function isNull(): bool;
    public function isNotNull(): bool;

    // Check if current value is EMPTY (0, null, false)
    public function isEmpty(): bool;
    public function isNotEmpty(): bool;

    // Check if the value is deemed touched/changed/inserted
    public function isFilled(): bool;
    public function isNotFilled(): bool;

    // Check if this variable is allowed to be serialized
    public function isEnabled(): bool;

    // Check if this variable is required to be filled/set
    public function isRequired(): bool;
}
```

When extending the class, these are the important variables to update

```php
use Seier\Resting\Fields\Field;

class CustomField extends Field
{
    // Set expected parameter with expected validator
    private Validator $validator;

    // Set expected parameter with expected parser
    private Parser $parser;

    public function __construct()
    {
        parent::__construct();

        // add Validator
        $this->validator = new Validator();

        // add Parser
        $this->parser = new Parser();
    }

    // Example Currency should probably return formatted number while raw is integer or float
    public function formatted(): mixed;

    // Type of expected raw value, if currency probably "int|float"
    public function get(): mixed;

    // Specift the type of this class
    // Read full description in Field abstraction class
    public function type(): array
    {
        return [
            'type' => '',
            'format' => '',
        ];
    }
}
```

### Resource Example

```php
use Seier\Resting;

class Resource extends Resting\Resource
{
    public IntField $id;
    public StringField $name;

    public function __construct()
    {
        $this->id = new IntField();
        $this->name = (new StringField())->nullable()->withDefault("John Doe");
    }
}
```
