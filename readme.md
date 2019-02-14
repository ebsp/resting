# Resting
Simple REST library for Laravel

This is a pendant to Laravel’s built in resources. It’s aimed to be more type aware, strict and to enforce a layer between models and API interfaces.

## Please note
This project is developed for use in personal projects. It's currently not advised to use in production projects, as breaking changes will happen.

## Resources
Resources are classes representing the data you want to send and receive in your API. Resources are made to separate your data layer from your API interface, making it more flexible. A resource is made up of fields (properties), each having a type. The field type is aware of the fields data type and validation criteria.

Resources should extend `Seier\Resting\Resource`.

### Fields
The Resting package comes with a number of predefined field types. The following field types are included:

- `ArrayField`
- `BoolField`
- `CarbonField`
- `DateField`
- `EnumField`
- `Field`
- `IntField`
- `NumberField`
- `PasswordField`
- `ResourceField`
- `ResourceArrayField`
- `StringField`

You may define your own depending on your needs. Each field type implements `protected function setMutator($value)` which is responsible for casting and/or validating the field input when being set.

An instance of a field type is defined on each of the resource’s properties through the resource’s constructor. For instance if the resource expose an attribute `id` the corresponding field type could be `IntField`

### An example

An example resource could look like:

```
class UserResource extends \Seier\Resting\Resource
{
	public $id;
	public $name;

	public function __construct()
	{
		$this->id = new IntField;
		$this->name = new StringField;
	}
}
```

## Todo

- [ ] Achieve 100% test coverage
- [ ] Clean up and finalize OAPI specs
- [ ] Add more documentation
