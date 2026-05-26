# Quickstart

This page walks through a complete `POST /users` endpoint with request validation, response shaping, and OpenAPI documentation — using nothing but Resting and Laravel.

## 1. Define a resource

```php
namespace App\Http\Resources;

use Seier\Resting\Resource;
use Seier\Resting\Fields\IntField;
use Seier\Resting\Fields\StringField;

class UserResource extends Resource
{
    public StringField $name;
    public StringField $email;
    public IntField $age;

    public function __construct()
    {
        $this->name = (new StringField())->trim()->minLength(1)->maxLength(120);
        $this->email = (new StringField())->trim()->lower()->maxLength(255);
        $this->age = (new IntField())->min(0)->max(150);
    }
}
```

By default every field is required and non-nullable. To make `age` optional, call `->notRequired()`; to allow `null`, call `->nullable()`.

## 2. Use it in a controller

Type-hint the resource and Resting's middleware will resolve it from the request body. Return a resource directly — `Resource` implements Laravel's `Jsonable`, so the framework JSON-encodes the response automatically.

```php
use App\Http\Resources\UserResource;

Route::post('/users', function (UserResource $input) {
    $user = User::create([
        'name'  => $input->name->get(),
        'email' => $input->email->get(),
        'age'   => $input->age->get(),
    ]);

    return (new UserResource())->set($user->toArray());
})->rest();
```

The `->rest()` route macro applies Resting's middleware, which converts the JSON body into the resource and surfaces validation errors with nested error paths. Returning the resource is enough — Laravel will call its `toJson()` method and emit the formatted response.

## 3. Validate input manually

If you'd rather construct the resource yourself instead of relying on the middleware:

```php
Route::post('/users', function (Request $request) {
    $input = (new UserResource())->set($request->all());
    // …
});
```

When validation fails, Resting throws `Seier\Resting\Exceptions\ValidationException`, which Laravel converts to a structured 422 response.

## 4. Map an Eloquent model to a response

```php
return (new UserResource())->set($user->toArray());
```

Or for a list:

```php
$resource = new UserResource();

return $resource->mapMany(
    User::all(),
    fn (UserResource $r, User $user) => $r->set($user->toArray()),
);
```

For paginated lists with metadata:

```php
return User::query()->asPaginatedResource(
    perPage: 25,
    resourceMapper: fn (User $u) => (new UserResource())->set($u->toArray()),
);
```

See [Eloquent integration](./eloquent) for more.

## 5. Generate an OpenAPI document

In a route or controller, hand Resting the route collection:

```php
use Illuminate\Support\Facades\Route;
use Seier\Resting\Support\OpenAPI;

Route::get('/openapi.json', function () {
    return new OpenAPI(Route::getRoutes());
});
```

Resting walks the routes, finds resource type-hints, and emits a `3.0.0` document containing your `UserResource` schema. Decorate routes with `->docs(...)` and `->lists(...)` to enrich descriptions and response types.

See [OpenAPI generation](./openapi) for the full picture.

## What's next

You now have a working endpoint. From here:

- [Resources](./resources) — the resource lifecycle, hooks, and output options.
- [Fields](./fields) — every field type and modifier.
- [Validation](./validation) — secondary validators, predicates, and cross-field rules.
- [OpenAPI generation](./openapi) — route metadata, security, and consumers.
