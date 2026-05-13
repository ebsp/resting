# Resting

**Typed REST resources, validation, and OpenAPI generation for Laravel.**

Resting is an alternative to Laravel's built-in API Resources, built around typed field objects. Each resource property is a strongly-typed `Field` that knows how to parse, validate, and format itself — so request payloads, validation rules, response shapes, and OpenAPI schemas all derive from a single source of truth.

```php
class UserResource extends \Seier\Resting\Resource
{
    public StringField $name;
    public IntField $age;

    public function __construct()
    {
        $this->name = new StringField();
        $this->age = new IntField();
    }
}
```

## Why Resting?

- **One definition, many uses.** A resource serves as the request schema, the validator, the response shape, and the OpenAPI component. No duplicated `FormRequest` + `Resource` + Swagger annotations.
- **Strongly typed fields.** Every field knows its own type, parser, and validators — no string-keyed validation rules to keep in sync with response transformers.
- **Composable validation.** Per-field validators, predicate-based conditional validation (`whenProvided`, `whenEquals`, …), and resource-level cross-field comparisons (`lessThan`, `equal`, …).
- **Polymorphic resources.** `UnionResource` for discriminator-tagged unions, `DynamicResource` for runtime field shapes.
- **OpenAPI 3 out of the box.** Generate a spec from your route collection with no extra annotations.

## Requirements

- PHP **8.2+**
- Laravel **11** or **12**

## Installation

```bash
composer require ebsp/resting
```

The service provider is auto-registered. Optionally publish the config:

```bash
php artisan vendor:publish --tag=config --provider="Seier\Resting\Support\Laravel\RestingServiceProvider"
```

## Quickstart

### Define a resource

```php
use Seier\Resting\Resource;
use Seier\Resting\Fields\IntField;
use Seier\Resting\Fields\StringField;

class UserResource extends Resource
{
    public StringField $name;
    public IntField $age;

    public function __construct()
    {
        $this->name = (new StringField())->trim();
        $this->age = (new IntField())->required();
    }
}
```

### Validate and parse a request

```php
Route::post('/users', function (Request $request) {
    $user = (new UserResource())->set($request->all());

    // Persist…
    return $user;
});
```

`Resource` implements Laravel's `Jsonable`, so returning the resource is enough — Laravel encodes it as JSON automatically. Invalid input throws `Illuminate\Validation\ValidationException` with nested error paths.

### Build a response

```php
return (new UserResource())->set($model->toArray());
```

Or map a collection:

```php
return (new UserResource())->mapMany(
    $users,
    fn (UserResource $r, User $u) => $r->set($u->toArray()),
);
```

## Field types

| Field | Description |
|---|---|
| `StringField` | Strings, with `trim()`, `upper()`, `lower()`, `stripWhitespace()`, `transform()` transformers |
| `IntField` | Integers, with numeric validators (`min`, `max`, `between`, …) |
| `NumberField` | Floats / decimals |
| `BoolField` | Booleans (parses `true/false`, `1/0`, `yes/no`) |
| `EnumField` | PHP backed enums (string or int) |
| `CarbonField` | Date-times (returns `CarbonImmutable`) |
| `CarbonPeriodField` | Date ranges as `CarbonPeriod` |
| `TimeField` | Time-of-day without date |
| `ArrayField` | Homogeneous arrays with element-type validation |
| `ResourceField` | Single nested resource |
| `ResourceArrayField` | Array of nested resources |
| `RawField` | Pass-through, no parsing or validation |

All fields support `required()`, `nullable()`, `forbidden()`, and `omittedDefault()`.

## Conditional validation

Predicate factories let you express rules like *"`shippingAddress` is required when `requiresShipping` is true"*:

```php
use function Seier\Resting\Validation\Predicates\whenEquals;

public function __construct()
{
    $this->requiresShipping = new BoolField();
    $this->shippingAddress = (new StringField())
        ->required(whenEquals($this->requiresShipping, true));
}
```

## Polymorphic resources

`UnionResource` discriminates between resource shapes by a tag field:

```php
class AnimalResource extends UnionResource
{
    public function __construct()
    {
        parent::__construct('type', fn () => [
            'cat' => new CatResource(),
            'dog' => new DogResource(),
        ]);
    }
}
```

`DynamicResource` lets you build resources at runtime:

```php
$resource = (new DynamicResource())
    ->withField('name', new StringField())
    ->withField('count', new IntField());
```

## OpenAPI generation

Resting can produce an OpenAPI 3 document from your registered routes:

```php
use Seier\Resting\Support\OpenAPI;

$spec = (new OpenAPI(Route::getRoutes()))->toArray();
```

Annotate routes with helper macros:

```php
Route::post('/users', UserController::class)
    ->docs('Create a new user.')
    ->lists(UserResource::class);
```

Resources, query parameters, and route metadata are turned into `components.schemas`, `components.parameters`, and `paths` automatically.

## Documentation

Full documentation, including all field types, validation predicates, the marshaller, and OpenAPI generation, is available at **[ebsp.github.io/resting](https://ebsp.github.io/resting)** *(coming soon)*.

## Testing

```bash
composer install
vendor/bin/phpunit
```

## Contributing

Issues and pull requests are welcome. Please run the test suite before submitting a PR.

## License

Resting is open-sourced software licensed under the [MIT license](LICENSE).
