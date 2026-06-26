# Configuration

Resting has a tiny configuration surface — most behaviour is controlled by the resources themselves. Two layers exist:

- **`config/resting.php`** — published via `vendor:publish`, drives OpenAPI metadata and the validation-exception class.
- **`RestingSettings`** — a runtime singleton that controls how resources serialize.

## `config/resting.php`

Publish the config file:

```bash
php artisan vendor:publish \
    --tag=config \
    --provider="Seier\Resting\Support\Laravel\RestingServiceProvider"
```

That writes the following to `config/resting.php`:

```php
return [
    'api_name' => 'Rest API',
    'version'  => '1',
    'validation_exception' => \Seier\Resting\Exceptions\ValidationException::class,
    'documentation' => [
        'servers' => [
            [
                'url' => env('APP_URL', 'http://localhost'),
                'description' => 'Local',
            ],
        ],
    ],
];
```

| Key | Used for |
| --- | --- |
| `api_name` | The OpenAPI document's `info.title`. |
| `version` | The OpenAPI document's `info.version` (cast to string). |
| `validation_exception` | The exception class thrown on validation failure. Override to customize how Laravel renders it. |
| `documentation.servers` | The OpenAPI document's `servers[]` block. Each entry is a free-form OpenAPI Server Object. |

## `RestingSettings`

`Seier\Resting\RestingSettings` is a process-wide singleton that controls how resources serialize.

```php
use Seier\Resting\RestingSettings;

RestingSettings::instance()->useImmutableCarbon = true;
RestingSettings::instance()->removeNulls = true;
RestingSettings::instance()->removeEmptyArrays = true;
```

| Property | Effect |
| --- | --- |
| `useImmutableCarbon` *(default `false`)* | When `true`, `CarbonField::get()` returns `CarbonImmutable` instances instead of mutable `Carbon`. |
| `removeNulls` *(default `false`)* | Strips `null` values from `toResponseArray()` output. |
| `removeEmptyArrays` *(default `false`)* | Strips `[]` values from `toResponseArray()` output. |

A typical place to set these is a service provider's `boot()` method:

```php
public function boot(): void
{
    RestingSettings::instance()->useImmutableCarbon = true;
    RestingSettings::instance()->removeNulls = true;
}
```

You can also override these on a per-resource basis with `Resource::removeNulls(?bool)` and `Resource::removeEmptyArrays(?bool)` — see [Resources › Removing nulls and empty arrays](./resources#removing-nulls-and-empty-arrays).

### Listening for validation errors

`RestingMiddleware` can notify you whenever it rejects a request because of validation errors, just before the `422` response is sent. Register a listener with `onValidationErrors()`:

```php
use Illuminate\Http\Request;
use Seier\Resting\RestingSettings;
use Seier\Resting\Validation\Errors\RequestValidationErrors;

RestingSettings::instance()->onValidationErrors(function (Request $request, RequestValidationErrors $errors) {
    Log::warning('Resting validation failed', [
        'path' => $request->path(),
        'errors' => $errors->all(),
    ]);
});
```

The listener receives the request and a `RequestValidationErrors` instance exposing `getBody()`, `getQuery()`, `getParam()`, and `all()`, plus `toException()` to build a `ValidationException` from the collected errors. It fires only when the request actually fails validation, and runs before the response is built — making it a convenient hook for logging or reporting. Pass `null` to clear a previously registered listener.

`RestingSettings::reset()` is provided for tests; the package's own test suite calls it in `setUp()` to ensure isolation.

## What's next

- [Testing](./testing) — assertions and test-suite setup.
- [Resources](./resources) — per-resource serialization options.
- [OpenAPI generation](./openapi) — how the `documentation` block flows into the spec.
