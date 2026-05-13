# Resources

A **Resource** is the central abstraction in Resting. It's a typed representation of a request body, response body, or any other JSON document your API exchanges. Each public property of a resource is a `Field` instance — and the resource ties them together with hydration, validation, output, and OpenAPI metadata.

Resources extend `Seier\Resting\Resource` and live in any namespace you like.

## Defining a resource

```php
use Seier\Resting\Resource;
use Seier\Resting\Fields\IntField;
use Seier\Resting\Fields\StringField;
use Seier\Resting\Fields\BoolField;

class ProductResource extends Resource
{
    public StringField $name;
    public IntField $priceCents;
    public BoolField $available;

    public function __construct()
    {
        $this->name = (new StringField())->trim()->maxLength(120);
        $this->priceCents = (new IntField())->min(0);
        $this->available = (new BoolField());
    }
}
```

Every public typed property whose value is a `Field` instance becomes part of the resource's shape. Other properties (private, untyped, non-Field) are ignored.

## The lifecycle

A resource's life looks like this:

1. **Construct** — your `__construct()` runs, instantiating each field with its options.
2. **Prepare** — `prepare(ResourceContext $context)` is called before any field receives input.
3. **Hydrate & validate** — the marshaller pushes input values into each field, invoking parsers and validators.
4. **Finish** — `finish()` is called once all fields are hydrated.
5. **Use** — read fields with `$resource->name->get()`, or serialize with `toResponseArray()`.

You can override `prepare()` and `finish()` to enable conditional fields, set default values, or do post-hydration work.

```php
class OrderResource extends Resource
{
    public StringField $type;
    public StringField $shippingAddress;

    public function __construct()
    {
        $this->type = (new StringField());
        $this->shippingAddress = (new StringField())->notRequired();
    }

    public function finish()
    {
        if ($this->type->get() === 'physical' && $this->shippingAddress->isNotFilled()) {
            // Custom post-hydration logic.
        }
    }
}
```

## Construction patterns

### From an array

```php
$resource = ProductResource::fromArray($request->all());
```

`fromArray()` instantiates a fresh resource, calls `prepare()`, validates and hydrates fields, then calls `finish()`.

### From a Laravel collection

```php
$resource = ProductResource::fromCollection(collect($payload));
```

### As a fluent builder

```php
$resource = (new ProductResource())->set($request->all());
```

`set()` is the chainable equivalent and is the most common form in controllers.

### From a raw, pre-shaped array

If you have a payload that's already in the exact response shape and don't need any field processing, store it as raw:

```php
$resource = ProductResource::fromRaw([
    'name'        => 'Widget',
    'priceCents'  => 999,
    'available'   => true,
]);

$resource->toArray();          // returns the raw array unchanged
$resource->toResponseArray();  // also returns the raw array unchanged
```

`fromRaw()` skips parsing, validation, and field formatting entirely.

It's especially useful for **composite response envelopes** — an outer resource that bundles several pre-formatted collections so the client gets everything in one round trip:

```php
class ActivityListResponse extends Resource
{
    public ResourceArrayField $activities;
    public ResourceArrayField $teachers;
    public ResourceArrayField $subjects;

    public function __construct()
    {
        $this->activities = new ResourceArrayField(ActivityResource::class);
        $this->teachers = new ResourceArrayField(TeacherResource::class);
        $this->subjects = new ResourceArrayField(SubjectResource::class);
    }
}

return ActivityListResponse::fromRaw([
    'activities' => (new ActivityResource())->mapMany($activities, fn ($r, $a) => $r->fromModel($a)),
    'teachers'   => (new TeacherResource())->mapMany($teachers, fn ($r, $t) => $r->fromModel($t)),
    'subjects'   => (new SubjectResource())->mapMany($subjects, fn ($r, $s) => $r->fromModel($s)),
]);
```

The envelope class still describes the response shape for OpenAPI; the controller assembles the data without re-validating anything.

## Reading values

Each property is a `Field`, so you read its value with `->get()`:

```php
$resource = ProductResource::fromArray($request->all());

$resource->name->get();        // string
$resource->priceCents->get();  // int
$resource->available->get();   // bool
```

## Output

| Method | Purpose |
| --- | --- |
| `toArray()` | Raw, unformatted values (whatever the parser produced). |
| `toResponseArray()` | Formatted values, suitable for a JSON response. |
| `toResponseObject()` | Same as `toResponseArray()` but as a `stdClass`. |
| `toJson($options)` | JSON-encoded response array. Implements `Jsonable`. |

The difference between `toArray()` and `toResponseArray()`:

- `toArray()` returns raw parser output — for instance, a `CarbonField` returns the `CarbonImmutable` object, an `EnumField` returns the enum case.
- `toResponseArray()` returns formatted output — Carbon dates become ISO strings, enums become their backing values, nested resources are recursively formatted.

Use `toArray()` for inter-resource conversion or to feed an Eloquent insert; use `toResponseArray()` when serializing to JSON.

### Filtering and renaming on output

Both `toArray()` and `toResponseArray()` accept three optional arguments:

```php
$resource->toResponseArray(
    filter: ['name', 'priceCents'],            // include only these
    rename: ['displayName' => 'name'],         // emit `name` as `displayName`
    requireFilled: true,                       // omit fields that were never set
);
```

`filter` accepts field names, `Field` instances, or a name → bool map for opt-out:

```php
$resource->toResponseArray(filter: [
    'name'  => true,
    'price' => false,
]);
```

`rename` accepts the same forms with target keys:

```php
$resource->toResponseArray(rename: ['displayName' => $resource->name]);
```

### Removing nulls and empty arrays

Two switches let you strip empties from the response:

```php
$resource
    ->removeNulls(true)
    ->removeEmptyArrays(true)
    ->toResponseArray();
```

These can also be set globally — see [Configuration](./configuration#restingsettings) for the `RestingSettings` singleton.

In practice, calling `removeNulls(true)` directly in the resource's `__construct()` is a common idiom — it bakes the preference into the resource itself rather than scattering it across callers:

```php
class UserResource extends Resource
{
    public IntField $id;
    public StringField $email;
    public IntField $teacher_id; // null for non-teacher users

    public function __construct()
    {
        $this->removeNulls(true);

        $this->id = new IntField();
        $this->email = (new StringField())->nullable();
        $this->teacher_id = (new IntField())->nullable();
    }
}
```

## Selecting fields with `only()`

`only()` enables a subset of fields and disables the rest:

```php
$resource->only($resource->name, $resource->priceCents);
$resource->toResponseArray(); // ['name' => …, 'priceCents' => …]
```

Disabled fields are skipped during hydration, output, and OpenAPI generation.

### Input variants from a shared base

A common pattern is to define **one read resource** with the full field set, then extend it for input variants (Create / Update / Patch) where each child calls `only()` in its own constructor to pick the relevant subset and tweak required-ness:

```php
class UserResource extends Resource
{
    public IntField $id;
    public StringField $name;
    public StringField $email;
    public IntField $age;

    public function __construct()
    {
        $this->id    = new IntField();
        $this->name  = new StringField();
        $this->email = new StringField();
        $this->age   = (new IntField())->nullable();
    }
}

class UserCreateResource extends UserResource
{
    public function __construct()
    {
        parent::__construct();

        $this->only(
            $this->name,
            $this->email,
            $this->age->notRequired(),
        );
    }
}
```

Calling `->notRequired()` on the field reference inline (as `$this->age->notRequired()` above) flips the requirement before `only()` registers it. This keeps the variant declarative — one place to read the input shape and its rules.

## Conditional field rules with `prepare()`

`prepare()` runs before any field is hydrated, with a `ResourceContext` exposing the raw input. Use it to flip field rules based on context that lives outside the input — the authenticated user, an environment flag, anything else.

```php
use App\Models\Inspector;

class AbsenceCreateResource extends AbsenceResource
{
    public function prepare(ResourceContext $context)
    {
        parent::prepare($context);

        // Only inspectors must specify which teacher the absence is for —
        // teachers always create absences for themselves.
        $this->teacher_id->required(auth()->user() instanceof Inspector);
    }
}
```

This is distinct from [predicates](./validation#predicates), which gate rules on other field values. Predicates are the right tool when one input field decides another field's rules; `prepare()` is the right tool when context outside the input does.

## Mapping many at once

`mapMany()` is a convenience for transforming an iterable of inputs into an array of formatted resource arrays:

```php
$resource = new UserResource();

$payload = $resource->mapMany($users, function (UserResource $r, User $user) {
    return $r->set($user->toArray());
});
```

The mapper can also accept a single argument if you don't need the prototype:

```php
$resource->mapMany($users, fn (User $u) => UserResource::fromArray($u->toArray()));
```

Each result has `toResponseArray()` called on it.

## Helpers

### Field metadata

```php
$resource->fields();                  // Collection of enabled Field instances
$resource->fields(filter: [...]);     // same filtering as toArray
```

Useful when you want to introspect a resource — for instance, to drive a custom OpenAPI walker or admin-form generator.

### Cross-field validation

The `Resource` class mixes in `ResourceValidation`, giving you cross-field comparisons:

```php
class DateRange extends Resource
{
    public CarbonField $from;
    public CarbonField $to;

    public function __construct()
    {
        $this->from = new CarbonField();
        $this->to = new CarbonField();

        $this->lessThan($this->from, $this->to);
    }
}
```

See [Validation](./validation#cross-field-validation) for the full set of comparison helpers and how to build custom resource-level validators.

## Variants: `Query` and `Params`

For query strings and path parameters, Resting ships two thin `Resource` subclasses that the middleware treats as input markers:

- **`Seier\Resting\Query`** — its fields come from `?key=value` query strings.
- **`Seier\Resting\Params`** — its fields come from path segments (`/users/{id}`).

They behave exactly like a `Resource` — same fields, same validation, same hooks — except the middleware marshals them in **string-based mode**, so `?age=42` parses cleanly into an `IntField`. Type-hint them on a controller method and the middleware hydrates them alongside body resources.

See [Routes & Macros › Query and Params](./routes#query-and-params) for the full treatment and examples.

## What's next

- [Fields](./fields) — every field type with examples.
- [Validation](./validation) — secondary validators and predicate-based conditional rules.
- [Polymorphic resources](./polymorphism) — `UnionResource` and `DynamicResource`.
- [Routes & Macros](./routes) — wiring resources, queries, and path params into Laravel routes.
