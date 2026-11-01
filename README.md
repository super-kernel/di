<p align="right">
  <strong>English</strong> | <a href="README.zh-CN.md">中文文档</a>
</p>

# SuperKernel DI

`super-kernel/di` is the PSR-11 shared-service container for SuperKernel and provides `psr/container-implementation` 2.0. Services are declared with native PHP Attributes from `super-kernel/attribute`, resolved on first lookup, and retained for the life of the container.

The container offers:

- interface mappings and factory providers declared with Attributes;
- constructor, method, and property injection, with lazy property references by default;
- custom property Attributes, definers, and resolvers as extension points;
- caller-owned resolution outside container retention.

It does not provide child containers, mutable registrations, transient scope, compiled containers, circular-dependency detection, or single-flight construction.

## Registering services

```php
use DateTimeImmutable;
use DateTimeZone;
use SuperKernel\Attribute\FactoryProvider;
use SuperKernel\Attribute\Provider;

interface ClockInterface
{
    public function now(): DateTimeImmutable;
}

#[Provider(ClockInterface::class)]
final class SystemClock implements ClockInterface
{
    public function now(): DateTimeImmutable
    {
        return new DateTimeImmutable();
    }
}

#[FactoryProvider(DateTimeZone::class)]
final class TimeZoneProvider
{
    public function __invoke(): DateTimeZone
    {
        return new DateTimeZone('UTC');
    }
}
```

- `#[Provider(Target::class)]` on an instantiable class maps `Target` to it. Mapping a class to itself registers it as a shared class. Mappings may chain, as in `interface -> interface -> concrete`, and every identifier in the chain retains the same instance. Self mappings on interfaces, abstract classes, or classes without public construction are rejected, as are incompatible and duplicate mappings.
- `#[FactoryProvider(Target::class)]` is repeatable. The factory provider must be instantiable, have no constructor or a public one, and declare its own public, non-static `__invoke()`, whose parameters are injected when it is called. Use it for custom construction, validation, or initialization; use `Provider` when normal construction suffices.
- Any other instantiable class is constructed directly when requested.

## Constructor and method injection

Each constructor or method parameter receives, in order:

1. a value supplied through `MethodDefinition`, matched by parameter name and then by position;
2. the container entry for its unique class or interface type;
3. its declared default value;
4. `null` when it is nullable.

Variadic parameters are omitted. By-reference parameters, ambiguous union types, and required parameters that none of these sources can satisfy are rejected.

## Property injection

```php
use SuperKernel\Attribute\Autowired;

final class ReportService
{
    #[Autowired]
    private ClockInterface $clock;

    public function generatedAt(): string
    {
        return $this->clock->now()->format(DATE_ATOM);
    }
}
```

An `#[Autowired]` property is resolved in this order:

1. a non-null `Autowired::$identifier`;
2. the one existing non-built-in class or interface in its type;
3. its declared default value;
4. otherwise, a property-resolution error.

Properties are assigned after the constructor returns, so a constructor cannot use them. Once an identifier exists, an unavailable entry is an error; the default is not used as a fallback. Public, protected, private, and uninitialized readonly properties are supported; static properties are rejected. `#[Autowired]` on a method is metadata only, and the container never calls that method.

Supported properties receive a PHP 8.4 lazy proxy: the entry is resolved through `Container::get()` the first time the proxy's state is read or written, and a failed initialization can be retried. The proxy is not identical (`===`) to the canonical entry. An interface or abstract identifier is proxied only through a `Provider` mapping to a concrete class. Ordinary lookup is used instead for `FactoryProvider`-only identifiers, internal classes other than `stdClass` and their subclasses, and classes without stored instance properties. Lazy initialization is synchronous and has no single-flight guard.

## Custom property Attributes

A package can map its own property Attribute to a handler without depending on this package:

```php
use Attribute;
use SuperKernel\Attribute\PropertyHandler;
use SuperKernel\Contract\ContainerInterface;
use SuperKernel\Contract\PropertyAttributeHandlerInterface;
use SuperKernel\Contract\PropertyContextInterface;

#[Attribute(Attribute::TARGET_PROPERTY)]
final readonly class FromEnv
{
    public function __construct(public string $name)
    {
    }
}

#[PropertyHandler(FromEnv::class)]
final readonly class FromEnvPropertyHandler implements PropertyAttributeHandlerInterface
{
    public function __construct(private ContainerInterface $container)
    {
    }

    public function handle(PropertyContextInterface $context, object $attribute): void
    {
        assert($attribute instanceof FromEnv);

        $value = getenv($attribute->name);

        if ($value !== false) {
            $context->assign($value);
        }
    }
}
```

- `#[PropertyHandler(TheAttribute::class)]` is repeatable, so one handler may serve several Attributes; each Attribute has at most one handler. Non-instantiable handlers, non-Attribute targets, targets without `Attribute::TARGET_PROPERTY`, and duplicate mappings are rejected.
- Every handler receives the root container through its constructor. Mapped Attributes are handled in declaration order; property Attributes without a handler are ignored.
- Not calling `assign()` leaves the property untouched, `assign(null)` assigns null, and a second assignment to the same property fails.

## Definers and resolvers

The container turns an identifier into a definition with the first supporting definer, then resolves the definition with the first supporting resolver. Higher priorities run first and equal priorities keep discovery order.

- `#[Definer(priority: N)]` marks an instantiable `SuperKernel\Di\Contract\DefinerInterface`. It has no required constructor parameter or requires only `AnnotationCollectorInterface`. The built-in definers are Provider `200`, FactoryProvider `100`, and Class `0`. Definitions and unsupported identifiers are cached per identifier.
- `#[Resolver(priority: N)]` marks a `SuperKernel\Di\Contract\ResolverInterface`, constructed with `SuperKernel\Contract\ContainerInterface`. The built-in resolvers are Factory `400`, Provider `300`, Class `200`, Property `100`, and Method `0`; a priority above `400` precedes them all. A resolver performs nested work by passing derived definitions to the container's `ResolverFactoryInterface`, not by calling another resolver. Priority selects the resolver for a definition; it does not order constructor or property injection.
- Both factories discover their definers and resolvers on first use. If an extension's constructor may yield a coroutine, perform the first lookup before sharing the container with concurrent work.

## Creating a container

```php
use SuperKernel\Contract\AnnotationCollectorInterface;
use SuperKernel\Di\Container;

/** @var AnnotationCollectorInterface $annotationCollector */
$container = new Container($annotationCollector);
```

Every container reserves these entries, which take precedence over supplied ones:

- `Container::class`, `SuperKernel\Contract\ContainerInterface`, and `Psr\Container\ContainerInterface` return the container itself;
- `AnnotationCollectorInterface` returns the supplied collector;
- `DefinitionFactoryInterface`, `DefinitionFactory::class`, `ResolverFactoryInterface`, and `ResolverFactory::class` return the container's own factories.

`new Container($annotationCollector, $entries)` also retains supplied services, including non-class identifiers and null values; class and interface identifiers must hold compatible instances.

`SuperKernel\Di\Provider\ContainerProvider` is the factory provider for both container interfaces. Invoked with a container, it returns that container unchanged. Invoked without one, it scans the application in an isolated process through `super-kernel/scan-isolation`, restores the published Composer and annotation caches, and returns a new root. The Process and Exec strategies re-run the current PHP script. Restore-only execution, including Phar, requires caches built by a normal scan with the deployed code; missing or incompatible caches fail without an implicit scan.

A bootstrapped root also retains these services under their interfaces and concrete classes: `AnnotationCollectorInterface`, `BasePathInterface`, `PackageManagerInterface`, `ReflectorInterface`, and `VendorDirInterface` from `SuperKernel\Contract`, `SuperKernel\Composer\Contract\ComposerMetadataInterface`, and `SuperKernel\ScanIsolation\Contract\ScanProcessorInterface`. Inject `VendorDirInterface` for the dependency directory; `PackageManagerInterface` does not expose it.

## Lookups and errors

- `get()` raises the PSR not-found exception for an identifier without a definition, and a PSR container exception when no resolver supports a known definition. PSR container exceptions from constructors and factories pass through unchanged; other failures are wrapped with the original as the cause.
- `has()` never resolves: it reports retained entries and identifiers with a definition, and a failing definition lookup reports `false`.
- For a class or interface identifier, the first resolved value must be a compatible instance. The first retained value for an identifier is never replaced.
- Circular dependencies are not detected and must not be declared. Concurrent first lookups of the same identifier may construct duplicates; the first completed value is retained.

## Caller-owned resolution

Resolve a definition without retaining the top-level result:

```php
use RuntimeException;
use SuperKernel\Di\Contract\DefinitionFactoryInterface;
use SuperKernel\Di\Contract\ResolverFactoryInterface;

/** @var DefinitionFactoryInterface $definitionFactory */
$definitionFactory = $container->get(DefinitionFactoryInterface::class);
/** @var ResolverFactoryInterface $resolverFactory */
$resolverFactory = $container->get(ResolverFactoryInterface::class);

$definition = $definitionFactory->get(ApplicationService::class)
    ?? throw new RuntimeException('ApplicationService has no definition.');

$callerOwnedService = $resolverFactory->resolve($definition);
```

This is not a transient scope: dependencies requested during that resolution still come from the shared container and may be retained there.

## Scan diagnostics

When the annotation scan skips classes, bootstrap logs the result through the container's `StdoutLoggerInterface`:

```text
Annotation scan skipped 1 class.
Annotation scan skipped %d classes.
Annotation scan skipped %s: %s: %s
```

The first two are WARNING summaries; the last is one INFO record per skipped class, in captured order, with the class, exception type, and message. ASCII control bytes are escaped in the message text only.

- Summary context: `event=annotation_scan.skipped_summary`, `skipped_count`, and `diagnostic_source=annotation_cache`.
- Detail context: `event=annotation_scan.skipped_class`, `diagnostic_source`, `sequence`, `package`, `class`, `exception_type`, and the raw `exception_message`.

The report is written once per root: in the scanning child, or in the current process for restore-only execution.

| Scan processor | Default logger output  | STDERR fallback  |
|----------------|------------------------|------------------|
| PCNTL          | Inherited STDOUT       | Inherited STDERR |
| Process        | Parent STDOUT          | Parent STDERR    |
| Exec           | Parent STDERR (merged) | Parent STDERR    |
| Null / Phar    | Current-process STDOUT | Current STDERR   |

- Without a logger binding, the report goes to STDERR in yellow and startup continues.
- If the logger or one of its dependencies fails to construct, or a write fails, the complete report goes to STDERR, the original error propagates, and startup stops; output written before the failure may repeat.
- Logger level filtering is respected without a fallback. To show only summaries or keep STDOUT clean for machine-readable output, bind an application logger with a WARNING threshold or a STDERR or file handler; see [the Logger README](https://github.com/super-kernel/logger/blob/main/README.md#scan-diagnostics). That logger also receives other framework records. DI has no verbosity flag or environment setting for this report.
- A custom logger is constructed in the scanning child. Its constructor must use injected services, must not call `ApplicationContext::getContainer()`, and must not depend on application startup. Buffered or asynchronous transports must account for the child's termination: a normal PSR-3 return means acceptance, not durable delivery.
