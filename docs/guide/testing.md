# Testing

Resting plugs into PHPUnit via Laravel's testing utilities. The package itself is tested with PHPUnit 11 and Orchestra Testbench 9/10; consumer projects can use any compatible test stack.

## Test-suite setup

Resting's own tests use Orchestra Testbench. If you're testing this package or building on top of it, your `TestCase` should look like:

```php
use Orchestra\Testbench\TestCase as Orchestra;
use Seier\Resting\Support\Laravel\RestingServiceProvider;
use Seier\Resting\RestingSettings;

abstract class TestCase extends Orchestra
{
    public function setUp(): void
    {
        parent::setUp();
        RestingSettings::reset();
    }

    protected function getPackageProviders($app): array
    {
        return [RestingServiceProvider::class];
    }
}
```

Calling `RestingSettings::reset()` in `setUp()` ensures one test mutating the global serialization settings doesn't leak into the next.

## `assertNestedJsonValidationErrors`

Resting registers an assertion macro on Laravel's `TestResponse` that reads the structured 422 envelope produced by the `->rest()` middleware:

```json
{
    "message": "…",
    "errors": {
        "body": [
            { "path": "email", "message": "Field is required." }
        ],
        "query": [],
        "param": []
    }
}
```

Use the macro in feature tests to assert specific errors:

```php
$this->postJson('/users', ['name' => 'Ada'])
    ->assertStatus(422)
    ->assertNestedJsonValidationErrors(['email']);
```

You can pass:

- A flat array of paths — asserts each path appears in the error list.
- A `path => substring` map — asserts the path appears **and** its message contains the substring.

```php
$this->postJson('/users', ['email' => ''])
    ->assertNestedJsonValidationErrors([
        'email' => 'must',  // message must contain "must"
    ]);
```

By default the macro inspects `errors.body`. To check `errors.query` or `errors.param`, pass the group as the second argument:

```php
->assertNestedJsonValidationErrors(['id'], 'param');
->assertNestedJsonValidationErrors(['page'], 'query');
```

## Constructing resources in tests

Resources have no required dependencies, so you can construct them inline:

```php
$resource = (new UserResource())->set([
    'name' => 'Ada',
    'email' => 'ada@example.com',
    'age' => 36,
]);

$this->assertSame('Ada', $resource->name->get());
```

For testing predicates and conditional rules, instantiate the resource and feed it different inputs to confirm validation outcomes:

```php
public function test_shipping_address_is_required_when_shipping(): void
{
    $this->expectException(\Seier\Resting\Exceptions\ValidationException::class);

    OrderResource::fromArray([
        'requiresShipping' => true,
        // shippingAddress missing
    ]);
}
```

## Asserting OpenAPI output

`OpenAPI::toArray()` returns plain arrays, which makes it easy to drill in:

```php
$spec = (new OpenAPI(Route::getRoutes()))->toArray();

$this->assertArrayHasKey('UserResource', $spec['components']['schemas']);
$this->assertSame(['name', 'email'], $spec['components']['schemas']['UserResource']['required']);
```

## Running the package's own test suite

```bash
composer install
vendor/bin/phpunit
```

The CI workflow (`.github/workflows/php.yml`) runs the suite against PHP 8.2, 8.3, and 8.4.

## What's next

- [Validation](./validation) — what assertions you might want to write.
- [Routes & Macros](./routes) — the response shape `assertNestedJsonValidationErrors` is built around.
