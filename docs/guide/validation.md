# Validation

Validation in Resting happens at three layers, from inside out:

1. **Type validators** — built into each field type. A `StringField` rejects non-strings, an `IntField` rejects non-integers, and so on.
2. **Field-level rules** — chainable methods that add extra constraints to a field (`minLength`, `between`, `in`, etc.).
3. **Resource-level validators** — cross-field rules that compare two or more fields.

Conditional behaviour is layered on top with **predicates**: tiny boolean expressions evaluated against the resource context that gate any of the above.

When validation fails, Resting throws `Seier\Resting\Exceptions\ValidationException`. The exception holds an array of error objects, each with a path that points back to the offending key in the input.

## Field modifiers

These four are the universal "is this field needed" toggles, available on every field. See [Fields › Common modifiers](./fields#common-modifiers) for full signatures.

- `required(bool|Predicate)` — must be present.
- `nullable(bool|Predicate)` — may be `null`.
- `forbidden(bool|Predicate)` — must not be present.
- `notRequired()` — shorthand for not-required + nullable.

All three accept a `Predicate` to make the rule conditional.

## Field-level rules

Each field type composes one or more **secondary validation traits** from `Seier\Resting\Validation\Secondary`. These give you typed, chainable methods.

### String fields

`StringField` and `EnumField` use `StringValidation`:

| Method | Effect |
| --- | --- |
| `length(int)` | Exact length. |
| `notEmpty()` | At least 1 character. |
| `minLength(int)` / `maxLength(int)` | Bounds. |
| `betweenLength(int $min, int $max)` | Both bounds in one call. |
| `matches(string $pattern)` | Must match the regex. |
| `digits(?int $length = null)` | Digits-only, optionally exact length. |
| `noWhitespace()` | No whitespace characters. |
| `hexColor(bool $acceptShort = true)` | `#rrggbb` or `#rgb`. |

```php
$this->slug = (new StringField())
    ->minLength(3)
    ->maxLength(80)
    ->matches('/^[a-z0-9-]+$/');
```

### Numeric fields

`IntField` and `NumberField` use `NumericValidation`:

| Method | Effect |
| --- | --- |
| `min(int\|float\|Field)` | `>= bound` |
| `max(int\|float\|Field)` | `<= bound` |
| `lessThan(int\|float\|Field)` | `< bound` |
| `greaterThan(int\|float\|Field)` | `> bound` |
| `between(int\|float, int\|float)` | Min + max combined. |
| `positive()` | `> 0` |
| `decimalCount(?int $min, ?int $max)` | Number of decimal digits (NumberField). |

When you pass another `Field` as the bound, the comparison is **late-bound**: it resolves to that field's value at validation time, allowing dynamic constraints like *"end ≥ start"*.

```php
$this->start = new IntField();
$this->end   = (new IntField())->min($this->start);
```

### Carbon fields

`CarbonField` uses `CarbonValidation`:

| Method | Effect |
| --- | --- |
| `min(CarbonInterface\|Field)` | `>= bound` |
| `max(CarbonInterface\|Field)` | `<= bound` |
| `after(CarbonInterface\|Field)` | `> bound` |
| `before(CarbonInterface\|Field)` | `< bound` |
| `between(CarbonInterface, CarbonInterface)` | Min + max combined. |

```php
$this->validFrom = new CarbonField();
$this->validTo   = (new CarbonField())->after($this->validFrom);
```

### Time fields

`TimeField` uses `TimeValidation` with the same shape: `min`, `max`, `after`, `before`, `between`, accepting either a `Time` value or another `Field`.

### Carbon period fields

`CarbonPeriodField` uses `CarbonPeriodValidation`:

| Method | Effect |
| --- | --- |
| `minInterval(CarbonInterval)` / `maxInterval(CarbonInterval, bool $allowWithoutEnd = true)` | Bounds on duration. |
| `minHours(int)` / `maxHours(int)` | Convenience for hour bounds. |
| `minDays(int)` / `maxDays(int)` | Convenience for day bounds. |
| `minWeeks(int)` / `maxWeeks(int)` | Convenience for week bounds. |

### Arrays

`ArrayField` uses `ArrayValidation`:

| Method | Effect |
| --- | --- |
| `size(int)` | Exact element count. |
| `notEmpty()` / `empty()` | Length convenience. |
| `minSize(int)` / `maxSize(int)` | Bounds. |

Element-level validation is configured through the element parser/validator passed to `ofStrings()`, `ofIntegers()`, `of(...)`, etc. See [Fields › ArrayField](./fields#arrayfield).

### Allow-list rules

`StringField`, `IntField`, and `EnumField` all use `InValidation`, providing:

```php
$this->status = (new StringField())->in(['active', 'archived']);
```

## Predicates

A **predicate** is a boolean function evaluated against a `ResourceContext` — a snapshot of all input values currently being marshalled. Predicates plug into `required`, `nullable`, `forbidden`, and `omittedDefault`/`nullDefault` to gate them on other fields.

The factories live in `Seier\Resting\Validation\Predicates`. Because they're in a namespace, import them with `use function`:

```php
use function Seier\Resting\Validation\Predicates\whenEquals;
use function Seier\Resting\Validation\Predicates\whenProvided;
use function Seier\Resting\Validation\Predicates\whenIn;
use function Seier\Resting\Validation\Predicates\any;
```

### Built-in predicates

| Predicate | True when |
| --- | --- |
| `whenProvided(Field ...$fields)` | All listed fields were present in the input. |
| `whenNotProvided(Field ...$fields)` | None of the listed fields were present. |
| `whenNull(Field ...$fields)` | All listed fields are `null`. |
| `whenNotNull(Field ...$fields)` | None of the listed fields are `null`. |
| `whenEquals(Field $field, mixed $expected)` | Field equals value (strict). |
| `whenNotEquals(Field $field, mixed $notExpected)` | Field does not equal value. |
| `whenIn(Field $field, array $oneOf)` | Field's value is in the array (strict). |
| `whenNotIn(Field $field, array $oneOf)` | Field's value is not in the array. |
| `when(Field $field, Closure $closure)` | Custom closure. The closure receives `(value, context, field)`. |

### Combinators

| Combinator | Result |
| --- | --- |
| `all(Predicate[] $predicates)` | True when every predicate passes. |
| `any(Predicate[] $predicates)` | True when at least one predicate passes. |
| `none(Predicate[] $predicates)` | True when no predicate passes. |

### Example: required-when

```php
use Seier\Resting\Resource;
use Seier\Resting\Fields\BoolField;
use Seier\Resting\Fields\StringField;

use function Seier\Resting\Validation\Predicates\whenEquals;

class OrderResource extends Resource
{
    public BoolField $requiresShipping;
    public StringField $shippingAddress;

    public function __construct()
    {
        $this->requiresShipping = new BoolField();

        $this->shippingAddress = (new StringField())
            ->required(whenEquals($this->requiresShipping, true));
    }
}
```

When `requiresShipping` is `true`, omitting `shippingAddress` is a validation error. When it's `false`, the field is implicitly optional.

### Example: forbidden-when

```php
use function Seier\Resting\Validation\Predicates\whenNotNull;

$this->giftMessage = (new StringField())
    ->forbidden(whenNotNull($this->orderType));
```

### Example: composed predicates

```php
use function Seier\Resting\Validation\Predicates\whenIn;
use function Seier\Resting\Validation\Predicates\any;

$this->refundReason = (new StringField())->required(any([
    whenEquals($this->status, 'refunded'),
    whenIn($this->status, ['pending_refund', 'partial_refund']),
]));
```

## Cross-field validation

Resources mix in `ResourceValidation`, providing methods that compare fields to other fields or constants. The comparison runs after individual field validation succeeds.

| Method | Asserts |
| --- | --- |
| `lessThan($left, $right)` | `$left < $right` |
| `lessThanOrEqual($left, $right)` | `$left <= $right` |
| `greaterThan($left, $right)` | `$left > $right` |
| `greaterThanOrEqual($left, $right)` | `$left >= $right` |
| `equal($left, $right)` | `$left == $right` |

`$left` and `$right` can be a `Field`, a scalar, or an array of either. When given arrays, every left value is compared against every right value.

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

### Custom resource validators

Implement `Seier\Resting\ResourceValidation\ResourceValidator` and register it:

```php
$this->addResourceValidator(new YourCustomValidator());
```

## Errors

When validation fails, `ValidationException` is thrown with an array of error objects. Each error has a `path` describing where in the input the problem occurred (`shippingAddress`, `items.2.quantity`, `author.email`, etc.). Laravel's exception handler converts these into a structured 422 JSON response when the request is processed via `->rest()` middleware.

To assert these errors in tests, see [Testing › `assertNestedJsonValidationErrors`](./testing#assertnestedjsonvalidationerrors).

## What's next

- [Marshalling](./marshalling) — how the marshaller surfaces nested error paths.
- [Fields](./fields) — every field type and its modifiers.
- [Polymorphic resources](./polymorphism) — `UnionResource` validation behaviour.
