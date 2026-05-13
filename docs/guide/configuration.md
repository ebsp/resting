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

`RestingSettings::reset()` is provided for tests; the package's own test suite calls it in `setUp()` to ensure isolation.

## What's next

- [Testing](./testing) — assertions and test-suite setup.
- [Resources](./resources) — per-resource serialization options.
- [OpenAPI generation](./openapi) — how the `documentation` block flows into the spec.
