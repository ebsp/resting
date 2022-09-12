# Resting

Helper classes to improve type awareness/restrictions on your REST API in Laravel.
The goal is to fail faster when receiving unexpected parameter types with helpful information in the callback.

[![Packagist PHP Version Support](https://img.shields.io/packagist/php-v/ebsp/resting?color=blue&style=for-the-badge)](https://www.php.net/releases/)
[![GitHub issues](https://img.shields.io/github/issues-raw/ebsp/resting?color=red&style=for-the-badge)](https://github.com/ebsp/resting/issues)
[![Packagist Stars](https://img.shields.io/packagist/stars/ebsp/resting?color=yellow&style=for-the-badge)](https://github.com/ebsp/resting/stargazers)

## **Notice**
This codebase is currently developed during personal usage.
Please be vary of using this in production as breaking changes can happen.

## Resources
Resources are classes representing the data you want to send and receive in your REST API.
Resources are intermediate data layers for your REST API Interface; making it more flexible.
A resource is made up of Fields (Type restricted roperties).
The Field is type aware of the parameter and validation criteria(s).

* Resources must extend `Seier\Resting\Resource`
* Fields must extend `Seier\Resting\Fields\Field`

## Setup

1. Add as service provider in Laravel (Should happen with Laravel Autodiscover)
2. Setup routing using middleware `Seier\Resting\Support\Laravel\RestingMiddleware`
3. Add Trait `Seier\Resting\Support\Laravel\UsesResting` to applicable `Controllers` and `Routes`

## Field Types
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

## Resources

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

## Extending Field Types

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

    // Specify the type of this class for OpenApi
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