# Introduction

Resting is a Laravel package for building strict, typed REST endpoints. It replaces the usual mix of `FormRequest` validators, API Resources, and OpenAPI annotations with a single abstraction: the **Resource**.

A Resource is a PHP class whose properties are typed `Field` objects. Each field knows how to:

- **Parse** input from a JSON payload, query string, or path parameter.
- **Validate** the parsed value against type constraints and any extra rules you attach.
- **Format** itself for an outgoing JSON response.
- **Describe** itself in an OpenAPI 3 schema.

That single definition then powers request parsing, response shaping, OpenAPI generation, and Eloquent transformation — without you having to repeat the field list anywhere else.

## A first look

```php
use Seier\Resting\Resource;
use Seier\Resting\Fields\IntField;
use Seier\Resting\Fields\StringField;

class UserResource extends Resource
{
    public StringField $name;
    public IntField $age;

    public function __construct()
    {
        $this->name = (new StringField())->trim()->maxLength(120);
        $this->age = (new IntField())->min(0);
    }
}
```

This single class can:

- Validate and parse `POST /users` request bodies.
- Be returned from a controller and serialized to a typed JSON response.
- Appear as a `components.schemas.UserResource` entry in your generated OpenAPI document.

## Why not Laravel's API Resources?

Laravel's built-in API Resources solve the response-shaping problem well, but the request side typically lives in a separate `FormRequest` with string-keyed rules, and OpenAPI documentation is yet another layer. Three sources of truth, three places to drift.

Resting flips this: **one resource definition is the source of truth**, and the rest is derived.

## Design principles

- **Strict by default.** Fields are required unless you opt them out. Type mismatches are validation errors, not silent coercion.
- **Composable.** Field rules, predicate-based conditional validation, and resource-level cross-field validators stack cleanly.
- **No magic strings.** Validation rules live as method calls on typed objects, not as string DSLs.
- **Laravel-native.** Service provider auto-registration, route macros, Eloquent helpers, PHPUnit assertions.

## What's next

- [Installation](./installation) — install the package and publish config.
- [Quickstart](./quickstart) — build a complete endpoint in five minutes.
- [Resources](./resources) — the `Resource` lifecycle in detail.
- [Fields](./fields) — every field type and its options.
