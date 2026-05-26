# Marshalling

The **marshaller** is the engine that turns a raw associative array (or `stdClass`) into a fully validated, fully typed Resource. You normally never call it directly — `Resource::set()`, `fromArray()`, and the `->rest()` route middleware all use it under the hood.

This page describes what happens during marshalling, why it matters, and how to interact with it when you need finer control.

## What the marshaller does

For each field on the target resource, the marshaller walks through these steps in order:

1. **Existence check** — was the key present in the input?
2. **Required / default** — if missing, either fail with a required error, apply a default value, or skip the field.
3. **Forbidden check** — if present, did the field's `forbidden` rule match? Then fail.
4. **Nullable check** — if the value is `null`, is the field nullable in this context?
5. **Parse** — invoke the field's parser (string-to-int, string-to-Carbon, etc.).
6. **Validate** — run the field's validators against the parsed value.
7. **Set** — assign the value to the field, marking it as filled.

After all fields are processed, the marshaller calls the resource's `finish()` hook and runs any [resource-level validators](./validation#cross-field-validation).

## Nested error paths

The marshaller tracks a path stack while it descends into nested resources. When an error occurs, the path is prepended to the error's `path` field, giving you exact pointers like:

```
items.2.quantity
billing.address.postalCode
```

This is what powers the structured validation responses returned by the `->rest()` middleware. See [Routes & Macros](./routes) for response shape.

## Public entry points

These are the marshalling APIs you'll most often see in user code:

### `Resource::set(array|Collection)`

The everyday entry point. Validates and hydrates a resource from an array.

```php
$user = (new UserResource())->set($request->all());
```

Throws `ValidationException` if any field fails.

### `Resource::fromArray(array)` / `Resource::fromCollection(Collection)`

Static convenience that constructs a fresh resource and calls `set()`.

```php
$user = UserResource::fromArray($request->all());
```

### `Resource::setFieldsFromCollection(Collection)`

The lower-level method that `set()` wraps. Useful if you already have a `Collection`.

### Manual marshalling

If you need direct access to the marshaller — for instance, to gather errors without throwing — instantiate it yourself:

```php
use Seier\Resting\Marshaller\ResourceMarshaller;

$resource = new UserResource();
$marshaller = new ResourceMarshaller();
$marshaller->marshalResourceFields($resource, (object) $request->all());

if ($errors = $marshaller->getValidationErrors()) {
    // handle errors yourself
}
```

`ResourceMarshaller::isStringBased(true)` switches the marshaller into string-based mode, where everything is treated as a string (useful for query strings or form data, where `'42'` should still parse as an integer).

## Defaults & predicates during marshalling

The marshaller is the only place where field defaults and predicate-gated rules are evaluated:

- **`omittedDefault`** values fire *only* when the key is absent from the input.
- **`nullDefault`** values fire whenever the resolved value would be `null`, regardless of whether the input had `null` or omitted the key.
- **`required` / `nullable` / `forbidden` predicates** are evaluated against a `ResourceContext` that exposes other fields' raw input values, so they can express *"X is required when Y was provided"*-type rules.

For more on this, see [Validation › Predicates](./validation#predicates).

## What's next

- [Validation](./validation) — the full set of validators and predicates.
- [Routes & Macros](./routes) — how the `->rest()` middleware uses the marshaller.
- [Polymorphic resources](./polymorphism) — how unions and discriminators flow through the marshaller.
