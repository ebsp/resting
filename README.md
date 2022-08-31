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

Resources must extend `Seier\Resting\Resource`.

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

<!-- This should be its own section of how Fields work and how to build custom types -->
You may define your own depending on your needs. Each field type implements `public function set($value)` which is responsible for casting and/or validating the field input when being set.

An instance of a field type is defined on each of the resource’s properties through the resource’s constructor. For instance if the resource expose an attribute `id` the corresponding field type could be `IntField`

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

