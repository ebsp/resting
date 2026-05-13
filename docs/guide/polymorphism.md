# Polymorphic Resources

Most resources are static — fixed shape, fixed fields. But REST APIs sometimes need shapes that vary at runtime: a tagged union of event types, a flexible search payload, a generic webhook envelope. Resting supports two patterns for this:

- **`UnionResource`** — for tagged unions where one field discriminates between several known sub-resources.
- **`DynamicResource`** — for resources whose fields are defined at runtime, not at class-definition time.

## `UnionResource`

A `UnionResource` represents *one of* several sub-resources, distinguished by a **discriminator** field — for example, a webhook payload where `type: "user.created"` and `type: "user.deleted"` carry different shapes.

### Defining a union

The base class declares the shared fields and registers the sub-resources:

```php
use Seier\Resting\UnionResource;
use Seier\Resting\Fields\StringField;

abstract class EventBase extends UnionResource
{
    public StringField $type;
    public StringField $eventId;

    public function __construct()
    {
        parent::__construct('type', fn () => [
            'user.created'  => new UserCreatedEvent(),
            'user.deleted'  => new UserDeletedEvent(),
        ]);

        $this->type = new StringField();
        $this->eventId = new StringField();
    }
}
```

`UnionResource::__construct(string $unionDiscriminator, Closure $unionResourcesFactory)` takes:

- The **discriminator key** (`'type'` here).
- A **factory closure** that returns the map of `discriminatorValue => Resource instance`.

Sub-resources extend the base and declare their own additional fields:

```php
class UserCreatedEvent extends EventBase
{
    public StringField $email;

    public function __construct()
    {
        parent::__construct();
        $this->email = new StringField();
    }
}

class UserDeletedEvent extends EventBase
{
    public StringField $reason;

    public function __construct()
    {
        parent::__construct();
        $this->reason = (new StringField())->notRequired();
    }
}
```

### Using a union

Pass the **base** class to the marshaller. It reads the discriminator, picks the right sub-resource, and hydrates that one:

```php
$event = EventBase::fromArray([
    'type' => 'user.created',
    'eventId' => 'evt_abc',
    'email' => 'a@b.com',
]);

$event::class;        // UserCreatedEvent
$event->email->get(); // 'a@b.com'
```

If the discriminator is missing or its value isn't in the map, the marshaller emits an `UnknownUnionDiscriminatorValidationError` with the path pointing at the discriminator key.

### OpenAPI behaviour

A `UnionResource` becomes an OpenAPI schema with `oneOf` referencing each sub-resource. The discriminator field is rendered with an `enum` of the matching value in each sub-schema. See [OpenAPI generation](./openapi#polymorphic-schemas).

### Caveats

- Sub-resources must extend the base directly. Multi-level inheritance is supported (the union's discriminator only fires at the top level), but each leaf must be reachable via the factory.
- Sub-resources must have no required constructor parameters.

## `DynamicResource`

A `DynamicResource` lets you define fields at runtime instead of as class properties. Use it when the shape isn't known until you've, for example, read user-supplied configuration.

```php
use Seier\Resting\DynamicResource;
use Seier\Resting\Fields\StringField;
use Seier\Resting\Fields\IntField;

$resource = (new DynamicResource())
    ->withField('name', new StringField())
    ->withField('age', (new IntField())->min(0));

$resource->set([
    'name' => 'Ada',
    'age'  => 200,
]);

$resource->name->get(); // 'Ada'
$resource->age->get();  // 200
```

`withField(string $property, Field $field)` registers a field with a property name. After registration you can read it via `__get` (`$resource->age`).

Accessing an unregistered property throws `Seier\Resting\Exceptions\DynamicResourceFieldException`.

### When to use which

| Use `UnionResource` when… | Use `DynamicResource` when… |
| --- | --- |
| You have a small, known set of variants. | The shape comes from configuration, user input, or another runtime source. |
| You want OpenAPI to describe each variant. | OpenAPI documentation isn't required for this resource. |
| You want each variant to be its own typed PHP class. | You're treating the resource as a generic bag. |

For everything else — the vast majority of cases — a regular `Resource` is the right choice.

## What's next

- [Marshalling](./marshalling) — how the marshaller resolves the discriminator and surfaces error paths into nested resources.
- [OpenAPI generation](./openapi) — how unions are rendered in the spec.
