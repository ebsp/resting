# Fields

A **Field** is a typed, self-aware property of a Resource. Every field knows its own value, parser, validators, default values, and OpenAPI schema.

This page covers:

- The shared lifecycle and modifiers (`required`, `nullable`, `forbidden`, defaults).
- Each built-in field type with idiomatic examples.

## Common modifiers

Every field type supports these chainable modifiers, defined on the base `Field` class.

### `required(bool|Predicate $state = true)`

Whether the field must be provided in the input. Fields are **required by default** — call `->required(false)` or `->notRequired()` to opt out.

```php
$this->name = (new StringField())->required();         // default
$this->nickname = (new StringField())->required(false); // optional
```

Pass a `Predicate` to make the requirement conditional. See [Validation › Predicates](./validation#predicates).

### `nullable(bool|Predicate $state = true)`

Whether the field accepts `null`. Fields are **non-nullable by default**. Calling `->required(false)` automatically makes the field nullable as well.

```php
$this->phone = (new StringField())->nullable();       // null allowed
$this->phone = (new StringField())->notNullable();    // explicit
```

### `forbidden(bool|Predicate $state = true)`

Whether the field is forbidden from being provided. Useful with predicates to express *"X must not be provided when Y is true"*.

### `notRequired()`

Shorthand for `->required(false)->nullable(true)`.

### `omittedDefault(mixed $value, ?Predicate $predicate = null)`

A default value used when the key is **not provided** in the input. Not applied when `null` is explicitly provided.

```php
$this->page = (new IntField())->omittedDefault(1);
```

The value can also be a `Closure` that returns the default at hydration time.

Multiple `omittedDefault` calls can be stacked; each may have an optional `Predicate` and the first one whose predicate passes wins.

### `nullDefault(mixed $value, ?Predicate $predicate = null)`

A default value used whenever the field is `null`, regardless of whether `null` was explicit or just omitted. Evaluated *after* `omittedDefault`.

### `withDefault(mixed $value, ?Predicate $predicate = null)`

A combined helper: marks the field as not-required and nullable, then registers a `nullDefault`. Use this when you want a single line that says "optional with this default."

```php
$this->limit = (new IntField())->withDefault(25);
```

### `enable(bool $state = true)` / `disable()`

Toggle whether a field participates in hydration, output, and OpenAPI generation. Used internally by `Resource::only()`.

## Field types

### `StringField`

```php
$this->name = (new StringField())->trim()->maxLength(120);
```

Parses and stores a string. Supports the [string transformers](#stringfield-transformers) and the [string validation methods](./validation#string-fields).

#### `StringField` transformers {#stringfield-transformers}

Transformers run on `set()` before validation, mutating the incoming string:

| Method | Effect |
| --- | --- |
| `trim()` | PHP `trim()` |
| `upper()` | `mb_strtoupper()` |
| `lower()` | `mb_strtolower()` |
| `stripWhitespace()` | Removes all whitespace via `preg_replace('/\s+/', '')` |
| `transform(callable $fn)` | Applies a custom callable; chainable, runs in registration order. |

```php
$this->slug = (new StringField())
    ->lower()
    ->stripWhitespace()
    ->transform(fn (string $v) => str_replace('-', '_', $v));
```

#### `emptyStringAsNull(bool $state = true)`

Treats an empty string in the input as `null`. Useful when accepting query strings or HTML form data.

#### `getNotEmpty(bool $trim = false)`

Returns `null` when the value is empty (or whitespace-only when `$trim = true`), otherwise the string.

### `IntField`

```php
$this->age = (new IntField())->min(0)->max(150);
```

Parses integers. Accepts numeric strings (`"42"` → `42`) and floats whose fractional part is zero (`42.0` → `42`).

Inherits the [numeric validation methods](./validation#numeric-fields) (`min`, `max`, `between`, `lessThan`, `greaterThan`, `positive`).

### `NumberField`

```php
$this->price = (new NumberField())->min(0)->decimalCount(max: 2);
```

Parses floats and decimals. Same numeric validation surface as `IntField`, plus `decimalCount(?int $min, ?int $max)` for bounding fractional digits.

### `BoolField`

```php
$this->active = new BoolField();
```

Parses booleans. Accepts `true`, `false`, `1`, `0`, `"true"`, `"false"`, `"1"`, `"0"`, `"yes"`, `"no"` (case-insensitive).

### `EnumField`

Backs a PHP **string-backed** enum. (Integer-backed enums are not supported.)

```php
enum Status: string {
    case Active = 'active';
    case Archived = 'archived';
}

$this->status = new EnumField(Status::class);
$this->status->get(); // Status::Active
```

Accepts either the enum case directly or its backing string value. The output (`toResponseArray`) emits the backing value.

### `CarbonField`

```php
$this->createdAt = new CarbonField();
$this->createdAt = (new CarbonField())->withIsoDateFormat();          // dates only
$this->createdAt = (new CarbonField())->withFormat('Y-m-d H:i:s');    // custom
```

Parses Carbon date-times. The `get()` method returns a `Carbon\Carbon` or `Carbon\CarbonImmutable` (controlled by `RestingSettings::useImmutableCarbon`).

| Method | Effect |
| --- | --- |
| `withFormat(string $format)` | Use the same format for input and output. |
| `withInputFormat(string $format)` | Parsing format only. |
| `withOutputFormat(string $format)` | Formatting format only. |
| `withIsoDateFormat()` | Shorthand for date-only ISO 8601 (`Y-m-d`). |
| `emptyStringAsNull(bool $state = true)` | Treat `""` as `null`; also makes the field nullable. |

Validation: see [Carbon fields](./validation#carbon-fields) (`min`, `max`, `before`, `after`, `between`).

### `CarbonPeriodField`

Parses a `Carbon\CarbonPeriod` from start/end values. Useful for date-range query parameters.

### `TimeField`

Parses a time-of-day (`Time` value object) without a calendar date. Validation: see [Time fields](./validation#time-fields).

### `ArrayField`

```php
$this->tags = (new ArrayField())->ofStrings();
$this->ids  = (new ArrayField())->ofIntegers(nullable: true);
```

A homogeneous list, where every element is parsed and validated against the same element type.

| Helper | Element type |
| --- | --- |
| `ofStrings()` | strings |
| `ofIntegers()` | integers |
| `ofNumbers()` | floats |
| `ofBooleans()` | booleans |
| `ofTimes()` | times |
| `ofCarbons()` | Carbon date-times |
| `ofArrays()` | nested arrays |
| `ofEnums(string\|ReflectionEnum $enumType)` | a backed enum |
| `of(PrimaryValidator $v, Parser $p)` | custom |

Each helper accepts an optional `?callable $config` to tune the validator/parser, plus a `?bool $nullable` flag to allow `null` elements.

```php
$this->codes = (new ArrayField())->ofStrings(
    config: fn ($validator, $parser) => $validator->withValidator(
        new \Seier\Resting\Validation\Secondary\String\StringRegexValidator('/^[A-Z]{3}$/')
    ),
);
```

`ArrayField` also exposes the [array validation methods](./validation#arrays) (`min`, `max`, `between`, `unique`).

### `ResourceField`

```php
$this->author = new ResourceField(AuthorResource::class);
$this->author = new ResourceField(fn () => new AuthorResource()); // factory form
```

A nested single resource. Pass a class string (the constructor must take no required parameters), or a closure that returns a Resource instance.

`get()` returns the nested `Resource`. `set()` accepts a Resource, a `Resourcable`, an array, or a `Collection`.

Helpers:

- `apply(Closure $apply)` — operate on the nested resource in place.
- `applyNullable(mixed $value, Closure $apply)` — like `apply`, but sets the field to `null` when `$value` is `null`.
- `resourceAsDefault()` — register the empty resource as a default value when the field is null.

### `ResourceArrayField`

A list of resources. Same construction as `ResourceField`, plus list-element validators.

### `RawField`

Pass-through. Accepts any value, performs no parsing or validation. Use sparingly — usually you want a more specific field.

### `EmptyStringAsNull` (trait)

A trait you can mix into custom string-shaped fields to support `emptyStringAsNull(bool)`.

## Defining custom fields

When the same field configuration repeats across many resources, extract it into a project-local field subclass. This keeps individual resources clean and centralizes domain rules.

### Reformatting a built-in field

A typical case is fixing a date-time output format across an entire API:

```php
namespace App\Api\Fields;

use Seier\Resting\Fields\CarbonField as RestingCarbonField;

class CarbonField extends RestingCarbonField
{
    public function __construct()
    {
        parent::__construct();

        $this->withOutputFormat('Y-m-d\TH:i:s');
    }
}
```

Resources then use `App\Api\Fields\CarbonField` instead of `Seier\Resting\Fields\CarbonField`, and every date-time response shares the same shape.

### Adding domain methods

You can layer extra parsing or accessor methods on top of a field. A common example is a sort-key field for query strings, where `?sort=name` means ascending and `?sort=-name` means descending:

```php
namespace App\Api\Fields;

use Illuminate\Support\Str;
use Seier\Resting\Fields\StringField;

class SortField extends StringField
{
    public function __construct(array $allowedKeys)
    {
        parent::__construct();

        $this->in(
            collect($allowedKeys)
                ->flatMap(fn (string $key) => [$key, "-$key"])
                ->all()
        );
    }

    public function getSortKey(): ?string
    {
        $value = parent::get();
        return $value === null ? null : ltrim($value, '+-');
    }

    public function getSortDirection(): ?string
    {
        $value = parent::get();
        return Str::startsWith($value ?? '', '-') ? 'desc' : 'asc';
    }
}
```

Used in a `Query`:

```php
class UserQuery extends Query
{
    public SortField $sort;

    public function __construct()
    {
        $this->sort = (new SortField(['name', 'created_at']))->notRequired();
    }
}
```

### Guidelines

- Subclass the most specific Resting field (`StringField`, `IntField`, `CarbonField`) — not `Field` — so you inherit parsing, formatting, and validation.
- Call `parent::__construct()` first.
- Keep custom fields free of dependencies that can't be resolved at constructor time. The OpenAPI generator and `ResourceField` both instantiate fields with no arguments when introspecting types, so prefer optional constructor parameters or zero-arg constructors.

## Putting it together

```php
class ArticleResource extends Resource
{
    public StringField $title;
    public StringField $slug;
    public ArrayField $tags;
    public CarbonField $publishedAt;
    public ResourceField $author;
    public EnumField $status;

    public function __construct()
    {
        $this->title = (new StringField())->trim()->minLength(1)->maxLength(200);

        $this->slug = (new StringField())
            ->lower()
            ->stripWhitespace()
            ->matches('/^[a-z0-9-]+$/');

        $this->tags = (new ArrayField())->ofStrings(
            config: fn ($v, $p) => null,  // tune the element validator if needed
        );

        $this->publishedAt = (new CarbonField())->nullable();

        $this->author = new ResourceField(AuthorResource::class);

        $this->status = (new EnumField(ArticleStatus::class))->withDefault(ArticleStatus::Draft);
    }
}
```

## What's next

- [Validation](./validation) — secondary validators per field type, predicates, and cross-field rules.
- [Marshalling](./marshalling) — how raw input becomes typed field values.
- [Polymorphic resources](./polymorphism) — `UnionResource` and `DynamicResource`.
