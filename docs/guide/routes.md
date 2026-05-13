# Routes & Macros

Resting registers a small set of route macros that:

- Resolve typed resources from request bodies, query strings, and path parameters.
- Attach human-readable docs and response-type metadata for OpenAPI generation.

All macros come from `RestingServiceProvider` and are available on Laravel's `Illuminate\Routing\Route` instance returned by `Route::get`/`Route::post`/etc.

## Applying the middleware

Resting's middleware does the heavy lifting:

- Validates the request body is JSON when one is sent.
- Inspects the route's controller signature, finds parameters typed as Resources/Query/Params subclasses, and hydrates them from the request.
- Replaces those parameter slots with the hydrated, validated objects.
- Returns a structured `422` response on validation errors *without* invoking the controller.

You can apply it per-route with the `->rest()` macro:

```php
Route::post('/users', UsersController::class . '@store')->rest();
```

Or — the typical pattern in larger applications — apply it to a whole route group:

```php
Route::group(['middleware' => 'rest'], function () {
    Route::post('/users', UsersController::class . '@store')
        ->docs('Create a user.');

    Route::get('/users', UsersController::class . '@index')
        ->lists(UserResource::class)
        ->docs('List users.');

    // … the rest of the API
});
```

Both forms register the same middleware (`Seier\Resting\Support\Laravel\RestingMiddleware`). Group-level application is the cleaner choice when most or all of your API uses Resting.

A 422 error response looks like:

```json
{
    "message": "One or more errors prevented the request from being fulfilled.",
    "errors": {
        "body":  [ { "path": "email", "message": "Field is required." } ],
        "query": [],
        "param": []
    }
}
```

The `body`, `query`, and `param` arrays correspond to errors from the JSON body, query string, and path parameters respectively.

## Resolving typed parameters

Inside the route closure or controller method, type-hint the resource/query/params class:

```php
public function store(UserResource $body, UserSearchQuery $query)
{
    // $body is a hydrated, validated UserResource (from the JSON body).
    // $query is a hydrated, validated UserSearchQuery (from ?…).
}
```

| Type-hint | Source | Behaviour |
| --- | --- | --- |
| Subclass of `Resource` (and not `Query` or `Params`) | JSON body | Body is decoded and marshalled. |
| Subclass of `Seier\Resting\Query` | Query string | All values treated as strings (`?age=42` → `IntField` 42). |
| Subclass of `Seier\Resting\Params` | Path parameters | Same string-based marshalling for `/users/{id}` etc. |
| Variadic resource (`Resource ...$bodies`) | Body | Body must be a JSON array; each element is marshalled into a resource. |
| Nullable resource (`?Resource $body`) | Body | An empty body resolves to `null` instead of a validation error. |

## `Query` and `Params`

`Seier\Resting\Query` and `Seier\Resting\Params` are thin abstract subclasses of `Resource`. They exist purely as **markers** — the middleware uses them to decide where to pull input from and how to parse it.

| Class | Pulls input from | Marshalling mode |
| --- | --- | --- |
| `Resource` | JSON request body | Native types (numbers stay numbers). |
| `Query` | `$request->query->all()` | String-based (`?age=42` reads as `'42'` and is parsed by the field). |
| `Params` | Route's `originalParameters()` | String-based (`/users/{id}` → `'42'` parsed by the field). |

Everything else — fields, validation, predicates, `prepare()`, `finish()`, `toArray()` — works identically. A `Query` is just a `Resource` that knows it's reading strings off the wire.

### When to use which

- **`Resource`** — JSON request bodies (`POST`, `PATCH`, `PUT`).
- **`Query`** — query-string filters, sort fields, pagination markers.
- **`Params`** — typed access to path parameters, especially when you want IDs validated as integers (or UUIDs as strings of a fixed shape) rather than raw strings.

You can mix all three on a single controller method — the middleware resolves them independently:

```php
public function show(UserParams $params, UserDetailQuery $query): UserResource
{
    return (new UserResource())->fromModel(
        User::findOrFail($params->id->get()),
    );
}
```

### Why string-based parsing matters

URLs only carry strings. Even `?count=5&active=true` arrives as `['count' => '5', 'active' => 'true']`. Resting's parsers handle the conversion:

- `IntField` accepts `"42"` → `42`.
- `BoolField` accepts `"true"`, `"yes"`, `"1"` → `true` (and the false counterparts).
- `CarbonField` parses ISO date strings.
- `EnumField` accepts the backing string value.

The middleware sets the marshaller's `isStringBased` flag for `Query` and `Params`, which tells parsers and predicates to treat scalar strings as candidates for parsing instead of rejecting them as type errors.

### Defining a `Query`

Query fields are usually optional — a missing query parameter shouldn't fail the request. Reach for `->notRequired()` (or `->withDefault(...)`) liberally:

```php
use Seier\Resting\Query;
use Seier\Resting\Fields\IntField;
use Seier\Resting\Fields\StringField;
use Seier\Resting\Fields\BoolField;

class UserSearchQuery extends Query
{
    public StringField $name;
    public IntField $age;
    public IntField $page;
    public IntField $perPage;
    public BoolField $includeArchived;

    public function __construct()
    {
        $this->name = (new StringField())->trim()->notRequired();
        $this->age = (new IntField())->min(0)->notRequired();
        $this->page = (new IntField())->min(1)->withDefault(1);
        $this->perPage = (new IntField())->between(1, 100)->withDefault(25);
        $this->includeArchived = (new BoolField())->withDefault(false);
    }
}
```

Use it from a controller:

```php
public function index(UserSearchQuery $query)
{
    return User::query()
        ->when($query->name->isFilled(), fn ($q) => $q->where('name', 'like', '%' . $query->name->get() . '%'))
        ->when($query->age->isFilled(), fn ($q) => $q->where('age', $query->age->get()))
        ->paginate($query->perPage->get(), page: $query->page->get());
}
```

### Defining `Params`

`Params` are typically required — a path parameter is structurally part of the URL — and most fields will be IDs or short, well-formed identifiers:

```php
use Seier\Resting\Params;
use Seier\Resting\Fields\IntField;

class UserParams extends Params
{
    public IntField $id;

    public function __construct()
    {
        $this->id = (new IntField())->min(1);
    }
}
```

The field property names must match the route's path parameter names (`/users/{id}` → `public IntField $id`). The middleware reads `$route->originalParameters()` and marshals from there.

### Custom field types in queries

Custom fields shine in queries — see the [`SortField` example](./fields#defining-custom-fields) for handling `?sort=name` / `?sort=-name`. Domain-shaped query parameters (date ranges, comma-separated lists, pagination cursors) are good candidates for project-local field subclasses.

### OpenAPI

Query and Params fields are emitted as `parameters[]` entries in the OpenAPI document, with `in: query` and `in: path` respectively. The field's type, validators, and required-ness flow through automatically — see [OpenAPI generation](./openapi#paths).

## `->docs(string $text)`

Attaches a human-readable description used by the OpenAPI generator as the route's `description`.

```php
Route::post('/users', UsersController::class . '@store')
    ->rest()
    ->docs('Create a new user.');
```

## `->lists(...$resources)`

Marks the response as a list of one or more resource classes. The OpenAPI generator uses this to describe the response schema when the controller's return type isn't already a Resource subclass.

```php
Route::get('/users', UsersController::class . '@index')
    ->rest()
    ->lists(UserResource::class)
    ->docs('List users.');
```

You can pass multiple classes to describe a list whose elements may be any of several resources (typically used with `UnionResource`).

## Returning resources from controllers

`Resource` implements Laravel's `Jsonable`, so you can return one directly and Laravel will encode it as JSON:

```php
Route::post('/users', function (UserResource $body) {
    $user = User::create($body->toArray());
    return (new UserResource())->set($user->toArray());
})->rest();
```

No `JsonResponse` wrapping needed. Laravel calls the resource's `toJson()` method, which is equivalent to `json_encode($resource->toResponseArray())`.

## Response shapes

Resting doesn't enforce a particular wrapping format for response bodies — return a resource, an array, a `Responsable`, or a `JsonResponse` if you need full control over headers/status. Two things in the package emit specific shapes:

- **`PaginatedResponse`** — `{ "data": [...], "page": ..., "limit": ..., "total": ... }`. See [Eloquent integration › Paginated responses](./eloquent#paginated-responses).
- **Validation errors** — the 422 envelope above.

## Example: full endpoint

```php
use App\Http\Resources\UserResource;
use App\Http\Queries\UserSearchQuery;
use Illuminate\Support\Facades\Route;

Route::post('/users', function (UserResource $body) {
    $user = User::create($body->toArray());
    return (new UserResource())->set($user->toArray());
})
    ->rest()
    ->docs('Create a user.');

Route::get('/users', function (UserSearchQuery $query) {
    return User::query()
        ->when($query->name->isFilled(), fn ($q) => $q->where('name', 'like', '%' . $query->name->get() . '%'))
        ->asPaginatedResource(
            resourceMapper: fn (User $u) => (new UserResource())->set($u->toArray()),
        );
})
    ->rest()
    ->lists(UserResource::class)
    ->docs('List users with optional name search.');
```

## What's next

- [OpenAPI generation](./openapi) — how `->docs()` and `->lists()` flow into the spec.
- [Eloquent integration](./eloquent) — `asPaginatedResource` and `mapMany`.
- [Marshalling](./marshalling) — the engine the middleware uses to hydrate parameters.
