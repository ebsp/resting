# Eloquent Integration

Resting ships with helpers for the most common Eloquent → Resource workflows: mapping single models, mapping collections, and emitting paginated JSON responses.

These helpers are registered by `RestingServiceProvider` as macros on Eloquent's `Builder` and `Relation`, plus instance methods on the provider.

## `Resourcable`

When you implement `Seier\Resting\Support\Resourcable` on a model, the helpers below can call `$model->asResource()` to convert it without you supplying a mapper:

```php
use Seier\Resting\Support\Resourcable;

class User extends Model implements Resourcable
{
    public function asResource(): UserResource
    {
        return (new UserResource())->set($this->toArray());
    }
}
```

## Mapping a model to a resource

The simplest case — convert a single model into a response array:

```php
return (new UserResource())
    ->set($user->toArray())
    ->toResponseArray();
```

If the model implements `Resourcable`, this is even shorter:

```php
return $user->asResource()->toResponseArray();
```

## Mapping many models

Use `Resource::mapMany()` for a list of models:

```php
$resource = new UserResource();

return $resource->mapMany(
    User::all(),
    fn (UserResource $r, User $user) => $r->fromModel($user),
);
```

`mapMany()` runs the callable for each input, calls `toResponseArray()` on the result, and returns the formatted array. It's the canonical way to serialize an Eloquent collection — far more common than calling `toResponseArray()` in a `->map()` yourself.

The mapper can also accept a single argument when you don't need the prototype:

```php
$resource->mapMany($users, fn (User $u) => UserResource::fromArray($u->toArray()));
```

## `fromModel()` convention

Resting itself doesn't define a "from a model" method on `Resource` — there's no single signature that fits every project. The common convention is for each resource to define its own `fromModel()` (or `fromUser()`, `fromAccount()`, …) that copies the relevant fields from the model:

```php
class UserResource extends Resource
{
    public IntField $id;
    public StringField $email;
    public ResourceField $name;

    public function __construct()
    {
        $this->id    = new IntField();
        $this->email = new StringField();
        $this->name  = new ResourceField(NameResource::class);
    }

    public function fromModel(User $user): static
    {
        $this->id->set($user->id);
        $this->email->set($user->email);
        $this->name->apply(fn (NameResource $name) => $name->fromUser($user));

        return $this;
    }
}
```

Pair it with `mapMany()`:

```php
return (new UserResource())->mapMany(
    $users,
    fn (UserResource $r, User $u) => $r->fromModel($u),
);
```

This pattern keeps the model → resource translation in one place, makes the resource self-contained, and stays type-safe end-to-end.

## Hydrating nested resources

When a resource contains nested `ResourceField` or `ResourceArrayField` instances, two helpers turn them into idiomatic one-liners.

### `ResourceField::apply()`

`apply(Closure $apply)` lets you operate on the nested resource in place:

```php
$this->name->apply(fn (NameResource $name) => $name->fromUser($user));
```

For nullable nested resources, use `applyNullable()` — it sets the field to `null` when the source value is `null`, and otherwise calls your closure:

```php
$this->confirmed_by->applyNullable(
    $absence->confirmed_by,
    fn (UserReferenceResource $r, Userable $confirmedBy) => $r->fromModel($confirmedBy),
);
```

### `ResourceArrayField::setManyRaw()`

`setManyRaw(iterable $items, Closure $mapper)` is the killer pattern for hydrating a `ResourceArrayField` from a model collection. It calls `mapMany()` under the hood, so each element is converted and formatted in one pass:

```php
$this->schools->setManyRaw(
    $absence->getSchools(),
    fn (SchoolResource $r, School $s) => $r->fromSchool($s),
);
```

After `setManyRaw()`, the field holds the formatted arrays directly — there's no second round of resource hydration when the parent serializes. This is the standard idiom for read-only nested collections; use plain `set()` when you actually need typed `Resource` instances inside the field (for further processing).

## Paginated responses

Resting registers an `asPaginatedResource` macro on Eloquent `Builder` and `Relation`. It paginates the query, converts each model, and wraps the result in a `PaginatedResponse`.

```php
return User::query()->asPaginatedResource(
    perPage: 25,
    resourceMapper: fn (User $user) => (new UserResource())->set($user->toArray()),
);
```

The signature is:

```php
asPaginatedResource(
    int $limit = 15,
    ?callable $resourceMapper = null,
): PaginatedResponse
```

If the request has a `?limit=` query string, that value overrides the explicit `$limit`. When a `$resourceMapper` is omitted, models implementing `Resourcable` are converted via `$model->asResource()`.

The output `PaginatedResponse` is a Laravel `Responsable` and serializes to:

```json
{
    "data":  [ /* mapped items */ ],
    "page":  1,
    "limit": 25,
    "total": 134
}
```

### Use it with relations too

```php
return $organization->users()->asPaginatedResource();
```

## Lower-level helpers

The macros are thin wrappers around two methods on the service provider, which you can call directly when you need more control:

```php
use Seier\Resting\Support\Laravel\RestingServiceProvider;

$provider = app(RestingServiceProvider::class);

// Map a Laravel collection of models into mapped/formatted arrays.
$collection = $provider->mapCollection($models, $resourceMapper);

// Wrap a paginator's items in resource-formatted arrays and emit a PaginatedResponse.
return $provider->mapPagination($lengthAwarePaginator, $resourceMapper);
```

`mapCollection` filters out anything that isn't a `Resource` or `Resourcable`, applies the mapper (or `asResource()`), and returns a collection of formatted arrays.

## What's next

- [Routes & Macros](./routes) — `Route::rest()`, `Route::docs()`, `Route::lists()`.
- [Resources](./resources) — output options like `removeNulls` and `requireFilled`.
