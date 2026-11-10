<p align="right">
  <strong>English</strong> | <a href="README.zh-CN.md">中文文档</a>
</p>

# SuperKernel DI

`super-kernel/di` is the PSR-11 container of SuperKernel: it builds the services that classes declare with Attributes, injects their dependencies, and shares each one for the life of the container.

## Installation

```bash
composer require super-kernel/di
```

The package provides `psr/container-implementation` 2.0. The container reads the metadata that `skernel build` generates, so a project needs SKERNEL to build it.

## What it provides

- Services declared with the `#[Provider]` and `#[FactoryProvider]` Attributes, created on first lookup and then shared.
- Constructor and factory injection by class or interface type, without configuration.
- Property injection with `#[Autowired]`, as lazy references by default.
- Custom property Attributes, definers, and resolvers as extension points.
- The root container restored from the metadata of `skernel build`, without scanning at start.
- Resolution of a new object outside the container's sharing when the caller owns it.

It does not provide:

- child containers, scopes, or per-request lifetimes;
- registration or replacement of entries at run time;
- a compiled container;
- detection of circular dependencies;
- protection against two coroutines creating the same entry at once.

## Quick start

Declare an interface and the class that provides it. Save the interface as `src/Greeting/Greeter.php`:

```php
<?php

declare(strict_types=1);

namespace Src\Greeting;

interface Greeter
{
    public function greet(string $name): string;
}
```

Save the class as `src/Greeting/EnglishGreeter.php`:

```php
<?php

declare(strict_types=1);

namespace Src\Greeting;

use SuperKernel\Attribute\Provider;

#[Provider(Greeter::class)]
final class EnglishGreeter implements Greeter
{
    public function greet(string $name): string
    {
        return "Hello, {$name}!";
    }
}
```

Restore the container and look the service up. Save the script as `bin/greet.php`:

```php
<?php

declare(strict_types=1);

use Src\Greeting\Greeter;
use SuperKernel\Di\Provider\ContainerProvider;

require dirname(__DIR__) . '/vendor/autoload.php';

$container = new ContainerProvider()();

echo $container->get(Greeter::class)->greet('SuperKernel'), PHP_EOL;
```

Build the project and run the script:

```bash
skernel build
php bin/greet.php
```

It prints `Hello, SuperKernel!`.

## Public surface

| Name | Kind | Purpose |
| --- | --- | --- |
| `SuperKernel\Attribute\Provider` | Attribute | Maps an identifier to the annotated class; a class mapped to itself becomes a shared class. |
| `SuperKernel\Attribute\FactoryProvider` | Attribute | Creates an identifier by calling the annotated class's `__invoke()`, whose parameters are injected. |
| `SuperKernel\Attribute\Autowired` | Attribute | Injects a property after the constructor returns. |
| `SuperKernel\Attribute\PropertyHandler` | Attribute | Registers the handler of a custom property Attribute. |
| `SuperKernel\Contract\PropertyAttributeHandlerInterface` | Interface | Implemented by a property handler. |
| `SuperKernel\Contract\PropertyContextInterface` | Interface | Passed to a property handler to inspect the property and assign its value. |
| `SuperKernel\Attribute\Definer` | Attribute | Registers a definer with a priority. |
| `SuperKernel\Di\Contract\DefinerInterface` | Interface | Implemented by a definer, which turns an identifier into a definition. |
| `SuperKernel\Di\Contract\DefinitionInterface` | Interface | Implemented by a definition. |
| `SuperKernel\Attribute\Resolver` | Attribute | Registers a resolver with a priority. |
| `SuperKernel\Di\Contract\ResolverInterface` | Interface | Implemented by a resolver, which turns a definition into a value. |
| `SuperKernel\Di\Contract\DefinitionFactoryInterface` | Interface | Obtains the definition of an identifier from the container. |
| `SuperKernel\Di\Contract\ResolverFactoryInterface` | Interface | Resolves a definition without sharing the result. |
| `SuperKernel\Di\Container` | Class | The container; `new Container($collector, $entries)` creates one from an annotation collector. |
| `SuperKernel\Di\Provider\ContainerProvider` | Class | Restores the root container from the metadata of `skernel build`. |

## Behavior to know

- Run `skernel build` after `composer install` or `composer update` and after changing autoload rules or annotated classes, or start with `skernel run`, which builds first. When the metadata is missing or outdated, `ContainerProvider` raises `SuperKernel\Metadata\Exception\BuildRequiredException`, whose message ends with ``Run `skernel build` to generate the project metadata.`` A changed annotated class is not detected; the container keeps using the metadata of the last build.
- `get()` raises `SuperKernel\Di\Exception\NotFoundException`, a PSR-11 not-found exception, for an identifier without a definition, and `SuperKernel\Di\Exception\ResolutionException`, a PSR-11 container exception, for every other failure, with the original exception as its previous one. PSR-11 exceptions thrown by your constructors and factories pass through unchanged.
- An invalid declaration, such as two providers for one identifier, is reported at the first lookup that needs it, not by `skernel build`. Two providers for one identifier make every lookup of an entry not yet created fail.
- `has()` never creates an entry: it reports whether an identifier is kept or has a definition, and reports `false` when finding the definition fails.
- The first value created for an identifier is kept for the life of the container; a class or interface identifier must resolve to an instance of that type.
- A property is injected after the constructor returns, so the constructor cannot use it. A lazy property holds a proxy, which is not identical (`===`) to the container's entry.
- A circular dependency ends the process with a fatal error.

## Documentation

The developer documentation explains every capability with complete examples, the errors and their messages, and what not to do: [SuperKernel DI](https://github.com/super-kernel/.github/blob/main/docs/components/di.md).

## License

Released under the MIT License. See [LICENSE](LICENSE).
