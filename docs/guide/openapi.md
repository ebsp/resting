# OpenAPI Generation

Resting can generate an OpenAPI 3.0 document directly from your Laravel route collection. The fields, validators, and route metadata you've already declared are the source of truth — there are no separate annotations to maintain.

## Generating a document

Pass the application's route collection into `Seier\Resting\Support\OpenAPI` and either render it or return it from a route:

```php
use Illuminate\Support\Facades\Route;
use Seier\Resting\Support\OpenAPI;

Route::get('/openapi.json', function () {
    return new OpenAPI(Route::getRoutes());
});
```

`OpenAPI` implements `Arrayable` and Laravel's `Responsable`, so:

- `(new OpenAPI($routes))->toArray()` returns the document as a plain array.
- Returning the instance from a route emits a JSON response automatically.

The constructor walks the routes immediately, so `toArray()` is cheap once instantiated.

## What ends up in the document

### Top-level

```php
[
    'openapi' => '3.0.0',
    'info' => [
        'version' => (string) config('resting.version'),
        'title'   => config('resting.api_name'),
    ],
    'servers' => config('resting.documentation.servers'),
    'paths' => [ /* one entry per route */ ],
    'components' => [
        'schemas'    => [ /* every reachable Resource */ ],
        'parameters' => [ /* every Query/Params field */ ],
    ],
]
```

The `info` and `servers` blocks come from the published `config/resting.php`.

### Paths

For each route, the generator emits a path keyed by the URI and HTTP method:

| Source | Becomes |
| --- | --- |
| `->docs($text)` | The path `description`. |
| Resource type-hint on `POST` / `PATCH` / `PUT` | A `requestBody` with a `$ref` to the resource schema. |
| `Query`-typed parameter | `parameters[]` entries with `in: query`. |
| `Params`-typed parameter | `parameters[]` entries with `in: path`. |
| Controller return type | The 200 response schema (recursively traced through resources). |
| `->lists($resourceClass)` | Used as the response schema when the return type isn't itself a Resource. |

#### Variadic / list payloads

Type-hinting `Resource ...$items` on a `POST`/`PATCH`/`PUT` produces a request body of `{ data: [Resource, …] }` for non-union resources, or a top-level `oneOf` array for unions.

### Components / schemas

The generator emits a schema for every Resource it encounters:

- Field types come from each `Field::type()` and become the property's `type`/`format`.
- Required fields are listed in `required[]`.
- Validator descriptions (e.g. `"must be at most 120 characters"`) are concatenated into the property's `description`.
- Nested `ResourceField` / `ResourceArrayField` references emit `$ref`s and pull the referenced resource into the components.

Schema names are derived from the resource's PHP class name. The `App\Api\Resources\` prefix is stripped, and namespace separators become underscores. So `App\Api\Resources\Users\UserResource` becomes `Users_UserResource`.

### Polymorphic schemas

A `UnionResource` is rendered as a `oneOf` of its sub-resources' schemas. The discriminator field on each sub-schema gets an `enum` containing only that sub-resource's discriminator value, so clients can distinguish them statically.

```yaml
EventBase:
  oneOf:
    - $ref: '#/components/schemas/UserCreatedEvent'
    - $ref: '#/components/schemas/UserDeletedEvent'
UserCreatedEvent:
  type: object
  properties:
    type:
      type: string
      enum: ['user.created']
    email:
      type: string
```

## Decorating routes

The generator depends on a few hints to build a useful spec.

### `->docs($text)` — endpoint description

```php
Route::post('/users', UsersController::class . '@store')
    ->rest()
    ->docs('Create a user. Returns the persisted record.');
```

### `->lists(...$resources)` — list responses

When the controller doesn't have a Resource return type, mark its response shape:

```php
Route::get('/users', UsersController::class . '@index')
    ->rest()
    ->lists(UserResource::class);
```

Multiple classes describe a heterogeneous list:

```php
Route::get('/feed', FeedController::class . '@index')
    ->rest()
    ->lists(ArticleResource::class, AnnouncementResource::class);
```

### Return-type tracing

If the controller method has a return type that's a Resource subclass, that's enough — the generator uses it directly:

```php
public function show(int $id): UserResource
{
    return (new UserResource())->set(User::findOrFail($id)->toArray());
}
```

Union return types (`UserResource|GuestResource`) are emitted as a `oneOf` schema.

## Configuration

The OpenAPI generator reads from `config/resting.php`:

```php
return [
    'api_name' => 'Acme API',
    'version'  => '2024-05-01',
    'documentation' => [
        'servers' => [
            ['url' => 'https://api.acme.example', 'description' => 'Production'],
            ['url' => env('APP_URL', 'http://localhost'), 'description' => 'Local'],
        ],
    ],
];
```

See [Configuration](./configuration) for the full file.

## Static helpers

The `OpenAPI` class exposes a few utility methods you may need when wiring up custom integrations:

| Method | Purpose |
| --- | --- |
| `OpenAPI::resourceRefName(string $className)` | The component-schema name for a resource class. |
| `OpenAPI::componentPath(string $component, string $type = 'schemas')` | Builds a `#/components/{type}/{component}` reference. |

## Caveats

- Only `string`-backed enums are supported by `EnumField`; integer-backed enums won't render.
- The generator inspects controller signatures via reflection, which requires resources/query/params classes to have **no required constructor parameters**.
- Route metadata is attached via property assignment (`$route->_docs`, `$route->_lists`), so route caching that drops these properties (e.g. `php artisan route:cache` followed by a closure-based generator endpoint) will lose the metadata. Regenerate the OpenAPI document at runtime, not at build time.

## What's next

- [Routes & Macros](./routes) — how `->rest()`, `->docs()`, and `->lists()` work.
- [Polymorphic resources](./polymorphism) — how unions are described in the spec.
- [Configuration](./configuration) — the `config/resting.php` reference.
