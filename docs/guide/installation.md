# Installation

## Requirements

| Requirement | Version |
| --- | --- |
| PHP | 8.2 or newer |
| Laravel | 11.x or 12.x |
| `ext-json` | required |

Resting is regularly tested against PHP 8.2, 8.3, and 8.4.

## Install via Composer

```bash
composer require ebsp/resting
```

The package's service provider, `Seier\Resting\Support\Laravel\RestingServiceProvider`, is auto-registered through Laravel's package discovery. There's no manual provider registration step.

## Publish the configuration

Resting ships with a small config file. Publishing it gives you a `config/resting.php` you can edit:

```bash
php artisan vendor:publish \
    --tag=config \
    --provider="Seier\Resting\Support\Laravel\RestingServiceProvider"
```

The published file looks like this:

```php
return [
    'api_name' => 'Rest API',
    'version'  => '1',
    'validation_exception' => \Seier\Resting\Exceptions\ValidationException::class,
    'documentation' => [
        'servers' => [
            [
                'url' => env('APP_URL', 'http://localhost'),
                'description' => 'Local',
            ],
        ],
    ],
];
```

See [Configuration](./configuration) for what each option does and how it influences OpenAPI generation.

## Verify the install

A quick smoke test that the package is wired up correctly:

```php
use Seier\Resting\Resource;
use Seier\Resting\Fields\StringField;

class PingResource extends Resource
{
    public StringField $message;

    public function __construct()
    {
        $this->message = new StringField();
    }
}

$ping = (new PingResource())->set(['message' => 'hello']);
$ping->toResponseArray(); // ['message' => 'hello']
```

If that runs, you're ready to move on to the [Quickstart](./quickstart).

## Upgrading

Resting follows semantic versioning. Breaking changes only ship in major releases, and the `master` branch reflects the next major. See the [release notes on GitHub](https://github.com/ebsp/resting/releases) before upgrading across majors.
