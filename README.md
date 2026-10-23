# SuperKernel DI

`super-kernel/di` is the PSR-11 shared-service container for SuperKernel. A root `Container` receives one restored
`AnnotationCollectorInterface` snapshot, optionally accepts already-created shared entries, and internally creates
the canonical `DefinitionFactory` and `ResolverFactory`. Newly resolved entries are fully constructed and
property-processed before `Container::get()` retains them.
Third-party packages may map native property Attributes to shared handler contracts without registering mutable
container state. An individual `#[Autowired]` property uses a PHP 8.4 lazy proxy by default when its target supports
one, deferring lookup of the canonical entry; the root entry itself remains eager. The first retained value for an
identifier cannot be replaced. When an identifier names a class or interface, `Container::get()` verifies the first resolved value is a
compatible instance before retaining it. Cached lookups do not repeat that check; identifiers that are not class or
interface names may retain any PSR-11 value.

The component depends on `super-kernel/contract` for shared boundaries, `super-kernel/attribute` for
implementation-independent native PHP Attributes, `super-kernel/annotator` for restored package metadata, and the
Composer, Reflector, and Scan Isolation packages that supply the default bootstrap implementations. It does
not provide child containers, mutable registrations, transient scope, compiled-container behavior, or single-flight
construction.

## Source organization

Paths below are relative to `src/` and the package namespace.

| Directory | Responsibility |
| --- | --- |
| `Property` | `PropertyContext` and `PropertyDispatcher`: one property assignment and Attribute-handler dispatch. |
| `Storage` | `RetainedEntries`: write-once storage owned by one container. |
| `Definer / Definition / Resolver / Factory` | Definition discovery, values, resolution, and dispatch construction. |
| `PropertyHandler / Provider` | Attribute-specific injection and the container bootstrap factory. |

Implementation helpers retain an `@internal` boundary; a responsibility namespace does not make them extension points.

### Public namespaces

Use the following component declarations:

- `Property\PropertyContext`
- `Property\PropertyDispatcher`
- `Storage\RetainedEntries`

## Root ownership

Construct a root from an Annotator snapshot:

```php
use SuperKernel\Contract\AnnotationCollectorInterface;
use SuperKernel\Di\Container;

/** @var AnnotationCollectorInterface $annotationCollector */
$container = new Container($annotationCollector);
```

Every root reserves the following canonical entries:

- `Container::class`, `SuperKernel\Contract\ContainerInterface`, and `Psr\Container\ContainerInterface` return the
  root container.
- `AnnotationCollectorInterface` returns the constructor-supplied snapshot.
- `DefinitionFactoryInterface` and `DefinitionFactory::class` return the same container-owned definition factory.
- `ResolverFactoryInterface` and `ResolverFactory::class` return the same container-owned resolver factory.

Neither factory is supplied by the caller and neither can be replaced through normal container resolution.

`new Container($annotationCollector, $entries)` also retains supplied services, including mixed non-class IDs and
stored null values. Class and interface IDs must contain compatible instances. Reserved entries take precedence over
the supplied map, so each container keeps its own collector, container identity, definition factory, and resolvers.

`SuperKernel\Di\Provider\ContainerProvider` remains the DI factory provider responsible for root
bootstrap. It has no explicit constructor. Its
`__invoke(?Psr\Container\ContainerInterface $container = null): Psr\Container\ContainerInterface` method returns a
supplied container unchanged. A null input starts one outer Scan Isolation execution with a `ScannedInterface`
callback. The private `ContainerProvider::CLASS_NAMES` constant bounds the internal bootstrap annotation graph. In each
process, that finite graph creates a temporary container which resolves startup services through their providers;
application classes enter only the complete isolated scan.

During the false phase, nested Composer and Annotator work uses `PhaseScanProcessor` to execute in the already isolated
process. Composer publishes each package and its classmap, and Annotator publishes each package's class, method, and
property annotations. No nested child process is started. During the true phase, the same providers use the phase
processor to restore those validated caches. The full package classmaps and annotations are not scanned again;
the finite internal bootstrap graph still supplies the dependency definitions needed to read them. Missing or incompatible
caches fail without an implicit scan, including when Phar selects Null and skips the false phase.

Each phase constructs `new Container(...)` with the full collector and the current process's shared
base path, native Composer metadata, vendor directory, package manager, reflector, and original scan processor.
Those services are retained under their interfaces and concrete classes. The temporary container, finite collector,
its factories/resolvers, phase processor, and phase-bound reconciler are not transferred. Child-process instances
remain isolated; cache restoration creates the instances retained by the application container.

Once `Container` has been initialized, its retained concrete, framework, and PSR container entries satisfy subsequent
container lookups directly. Normal resolution therefore does not invoke `ContainerProvider` again. Another root
is created only when a caller explicitly invokes a provider without supplying an existing container.

After DI is ready, consumers inject `SuperKernel\Contract\VendorDirInterface` for dependency-directory access.
`PackageManagerInterface` supplies package and class-ownership data without a vendor-directory accessor.

DI owns bootstrap composition and default implementation selection. Annotator owns native Attribute extraction and
package annotation persistence, including extraction for the finite bootstrap graph. Scan Isolation owns the outer
child-process runner, phase delegation, and termination boundary.

## Scan diagnostic reporting

After the processor completes the worker callback, `AnnotationCollectorProvider` reads the just-published
cache. DI uses that complete collector, not the finite bootstrap collector, to construct the scanning child's final
container. If there are diagnostics, DI gets **that container's** `StdoutLoggerInterface`, reports, then returns null
from the false phase so the child ends. Developer `Provider` mappings are honored; DI never constructs Logger's
provider or implementation itself. With zero diagnostics it does not resolve the logging service.

Normal PCNTL, Process, and Exec true phases construct the parent's own container without resolving a logger for scan
diagnostics or repeating the report. `NullScanProcessor` is explicitly recognized as restore-only: it has no false
phase and reports in the current process after restoring. This is the only mode that creates the main-process logger
for scan diagnostics. The distinction uses the existing concrete processor chosen by DI; Scan Isolation's public
interfaces are unchanged. A future binary restore-only strategy needs an explicit integration decision.

The reporter reads the optional Contract `ScanDiagnosticsInterface` from the existing collector. It does not bind a
second collector service. The built-in bootstrap requires that capability. Hand-constructed `Container` instances
have no added logging side effect, and supplying an existing container to `ContainerProvider` still returns it unchanged.
Explicitly starting another root repeats the lifecycle; no global or cache-backed reported flag is used.

Exact messages are:

```text
Annotation scan skipped 1 class.
Annotation scan skipped %d classes.
Annotation scan skipped %s: %s: %s
```

The first two are singular/plural WARNING summaries. The last is one INFO message per class, in captured order, with
class, concrete exception type, and message. There is no forced trailing period on detail messages. ASCII control bytes
are escaped only in display text; no truncation or UTF-8 requirement applies to the captured data.

Summary context contains `event=annotation_scan.skipped_summary`, `skipped_count`, and
`diagnostic_source=annotation_cache`. Detail context contains `event=annotation_scan.skipped_class`, the same source,
`sequence`, `package`, `class`, `exception_type`, and raw `exception_message`. There is no fabricated PSR-3 `exception`
object. Logger owns timestamps, color, layout, threshold, and transport; DI owns these domain messages and levels.

### Failures and process streams

| Situation | Result |
| --- | --- |
| No logger binding | All diagnostics go to yellow STDERR and bootstrap continues. |
| Logger construction, dependency resolution, or write throws | All diagnostics fall back to STDERR, then the original error propagates in that process. Partial logging can therefore be duplicated. |
| Container construction fails after diagnostics restore | The report-owning phase falls back to STDERR and propagates the original error. |
| Logger deliberately filters records | No fallback; normal return respects the developer's logging policy. |
| Fallback itself fails | Preserve an existing primary error; without one, propagate the output error. |
| Application resolution/run fails after reporting | Preserve the existing application boundary; do not report diagnostics again. |

Only DI's direct `NotFoundException` for the `StdoutLoggerInterface` identifier means an absent optional binding.
`has()` is not used to hide faulty definitions, and a missing dependency of a registered logger remains an error.
No fallback calls the generic exception dispatcher or recursively uses the failing logger.

**Unsuccessful child status prevents parent startup, even after cache publication.** Scan Isolation rejects nonzero,
signaled, or unknown child results before the true phase; Process/Exec also require the callback acknowledgement.
A registered logger's dependency, construction, or write failure still emits the full STDERR fallback and preserves
the child error, then blocks startup through the parent's ScanProcessFailedException. Missing optional loggers with
successful fallback still permit startup. Repair failing logger services; normal level filtering remains supported.
A callback or outer
handler that explicitly exits 0 can still permit completion; status checking cannot prove normal callback return.
Restore-only errors occur in the current process and propagate directly to its caller.

| Selected processor | Default logger records ultimately appear on | Emergency fallback ultimately appears on |
| --- | --- | --- |
| PCNTL | Parent-visible STDOUT inherited by the child | Inherited STDERR |
| Process | Parent STDOUT, forwarded separately | Parent STDERR, forwarded separately |
| Exec | Parent STDERR, because child STDOUT and STDERR are merged | Parent STDERR |
| Null / Phar | Current-process STDOUT | Current-process STDERR |

A replacement writing to STDERR uses STDERR under all strategies; Exec cannot preserve an alternate stdout/stderr
separation. Log formatting can also depend on the child stream's TTY status. Do not infer the ultimate stream solely
from the `StdoutLoggerInterface` name.

### Output and deployment

Default Logger INFO emits both a
WARNING summary and INFO details, normally on STDOUT, with timestamp and formatter policy. To see only summaries,
replace the named logger with a WARNING threshold. To keep machine-readable stdout clean, choose STDERR or a file in
an application logger Provider; [the Logger README](https://github.com/super-kernel/logger/blob/main/README.md#scan-diagnostics) has runnable code. This also
changes other framework records using the same named service. DI adds no CLI verbosity flag or environment setting.

Custom loggers and their dependencies are constructed in the scan child when needed. They must use injected services
and **must not call `ApplicationContext::getContainer()` in their constructors**, which would reenter root creation.
They must not depend on application startup or resolve `ApplicationInterface` as part of construction. External side
effects from constructors still occur even though in-memory objects are isolated. Child logger instances are discarded;
when normal application work first needs a logger, the parent constructs its own service. Buffered or asynchronous
custom transports must account for child termination: a normal PSR-3 return means acceptance, not durable delivery.

Phar restores build diagnostics each time and therefore creates its logger in the current process. Rebuild schema 3
caches before deploying schema 4 code. Binary packaging is not implemented here, and existing Process/Exec still depend
on PHP script reentry. The root bootstrap sequence and framework application API are unchanged.

## Annotation-driven registration

The two container-owned factories initialize their ordered priority queues on first use. Each reads
`AnnotationCollectorInterface` back from the root's reserved entries, visits exact class metadata, reads each trusted
Attribute instance directly, validates the annotated implementation's DI relationships, and sorts higher integer
priorities first. Native Attribute construction, target, and repeatability validity belong to Annotator's published
data. Equal priorities preserve package and class discovery order. Successful initialization is retained by that
factory.

Factory initialization does not provide a cross-coroutine waiting primitive: a host whose third-party definer or
resolver constructors may yield should complete the first factory lookup before exposing the root to concurrent
application work.

### Definers

`DefinitionFactory` discovers exact `SuperKernel\Attribute\Definer` metadata. An annotated class must be an
instantiable `DefinerInterface`. It may have no required constructor parameter or accept the restored
`AnnotationCollectorInterface` as its one required bootstrap dependency.

The built-in definer priorities are:

1. `ProviderDefiner`: `200`
2. `FactoryDefiner`: `100`
3. `ClassDefiner`: `0`

The first supporting definer produces the definition. `DefinitionFactory` caches both successful definitions and
unsupported `null` results by requested identifier.

`ProviderDefiner` reads `#[Provider]` mappings. An instantiable class marked with `#[Provider(OwnClass::class)]`
registers itself as a `ClassDefinition`, using normal constructor and property injection and retaining one shared
instance. Self mappings on interfaces, abstract classes, or classes without public construction are rejected.
For a distinct compatible class or interface, the definer produces `ProviderDefinition`. `ProviderResolver` follows
the mapped identifier through the container, so declarations may form `interface -> interface -> ... -> concrete`
chains. Each resolved identifier retains the same terminal instance.

Use direct `Provider` registration when normal DI construction suffices. A `FactoryProvider` is needed only for
custom construction, validation, or initialization beyond forwarding constructor arguments.

`FactoryDefiner` reads repeatable `#[FactoryProvider]` mappings. The mapped factory-provider class must be instantiable;
it may omit its constructor and use PHP's implicit public constructor, while any available constructor must be public.
The factory provider must declare its own public, non-static `__invoke()` method. Invoke parameters are allowed because
they are resolved at invocation time. `ClassDefiner` supplies `ClassDefinition` for otherwise unmapped instantiable
classes.

Classes marked with `FactoryProvider` live under their owning package's `src/Provider/` path and corresponding
`Provider` namespace, and their names end with `Provider`. The `FactoryProvider` metadata identifies the factory role.
This also applies to
`SuperKernel\ScanIsolation\Provider\ScanProcessorProvider`, which remains directly invokable.

### Resolvers

`ResolverFactory` discovers exact `SuperKernel\Attribute\Resolver` metadata. An annotated class must implement
`ResolverInterface`, which declares this constructor contract:

```php
public function __construct(ContainerInterface $container)
```

The root container is passed to every discovered resolver. A resolver may therefore obtain the container-owned
dispatcher and submit a derived `DefinitionInterface` back to it, but it does not directly select or call another
resolver.

The built-in resolver priorities are:

1. `FactoryResolver`: `400`
2. `ProviderResolver`: `300`
3. `ClassResolver`: `200`
4. `PropertyResolver`: `100`
5. `MethodResolver`: `0`

The levels are spaced by `100`, leaving room for extensions between built-in resolvers. The Attribute's default
priority remains `0`; a priority greater than `400` precedes every built-in resolver. Extensions using absolute
priorities chosen against earlier levels may need adjustment.

Resolver priority determines which implementation receives a definition when more than one resolver reports support.
The built-in definition types are currently disjoint, while an extension may deliberately intercept a definition with
a higher-priority supporting resolver. Priority does not determine constructor or property-injection execution order.

### Property handlers

`PropertyResolver` owns a lazy property-handler dispatcher. The dispatcher discovers exact, repeatable
`SuperKernel\Attribute\PropertyHandler` metadata and maps each declared native property Attribute to one
`SuperKernel\Contract\PropertyAttributeHandlerInterface` implementation. Every handler receives the root
`ContainerInterface` through its constructor. The dispatcher rejects non-instantiable handlers, non-Attribute targets,
targets that do not allow `Attribute::TARGET_PROPERTY`, and duplicate mappings. It trusts the collected
`PropertyHandler` instance and does not reconstruct it from metadata arguments.

One handler class may map several Attribute classes. Each Attribute class has at most one handler, so discovery order
does not select an override. Unmapped property Attributes are unrelated metadata and remain untouched. Mapped
Attributes are instantiated through native reflection and dispatched in declaration order. A handler writes through
the shared `PropertyContextInterface`; omitting `assign()` skips the property, while `assign(null)` is an explicit null
assignment. A second assignment to the same property fails instead of silently replacing the first.

## Resolution pipeline

For a normal entry lookup, the call flow is:

```text
Container::get(identifier)
    -> DefinitionFactory::get(identifier)
    -> ResolverFactory::resolve(definition)
    -> first priority-ordered resolver whose support() returns true
    -> retain the produced value under identifier
```

Class construction performs nested dispatch rather than calling the method resolver directly:

```text
ResolverFactory::resolve(ClassDefinition)
    -> ClassResolver
    -> ResolverFactory::resolve(MethodDefinition(class, "__construct"))
    -> priority-ordered resolver dispatch
    -> MethodResolver
    -> resolved constructor arguments
    -> ClassResolver instantiates the class
    -> ResolverFactory::resolve(PropertyDefinition(class))
    -> PropertyResolver matches exact PropertyHandler mappings
    -> each mapped handler updates a shared property context
    -> PropertyResolver returns the ordered assigned property-name/value map
    -> ClassResolver assigns the reflected instance properties
```

Factory-provider invocation uses the same rule:

```text
ResolverFactory::resolve(FactoryDefinition)
    -> FactoryResolver
    -> Container::get(factory class)
    -> ResolverFactory::resolve(MethodDefinition(factory class, "__invoke"))
    -> priority-ordered resolver dispatch
    -> invoke the retained factory object with the returned arguments
```

Resolvers receive the root dependency-lookup boundary through their constructor. They do not hold references to one
another. Every derived definition is resubmitted to `ResolverFactory`, so priority and support selection remain
centralized.

`MethodDefinition` normalizes an omitted or `null` method name to `__construct`. Its custom parameters are selected by
parameter name first and reflection position second. If no custom value exists, unique class or interface dependencies
come from the root container, followed by declared defaults or nullable fallbacks. Variadics are omitted and
by-reference parameters are rejected. A class without a constructor produces an empty argument list; an explicitly
requested missing method fails.

Existing PSR container exceptions from collaborators are preserved. Foreign failures are normalized by the factory or
resolver that owns the failed operation. An identifier without a definition raises the PSR not-found contract, while a
known definition without a supporting resolver raises a container resolution error. `has()` checks retained values and
definitions without invoking a resolver and converts definition lookup failures to `false`.

## Property injection and lazy references

`PropertyResolver` reflects the requested class and visits its properties and native Attributes in reflection order. It
ignores Attributes without a discovered `#[PropertyHandler]` mapping and returns each explicit handler assignment in an
insertion-ordered associative array whose key is the member property name. `ClassResolver` applies that map after
constructor invocation and before the instance is returned for root retention.

`SuperKernel\Di\PropertyHandler\AutowiredPropertyHandler` is the built-in
`#[PropertyHandler(Autowired::class)]` implementation. It owns identifier inference, default values, native lazy
proxies, and PSR exception propagation. Third-party
integrations declare their own property Attribute, annotate a handler class with
`#[PropertyHandler(TheAttribute::class)]`, and implement the shared Contract interfaces; they do not need a production
dependency on this concrete DI package.

`AutowiredPropertyHandler` consumes `Autowired` and its optional `identifier`. `Autowired` on methods
remains declaration metadata; DI does not invoke those methods automatically. Rebuild annotation caches through a
normal scan before restore-only or packaged execution so serialized records contain the current Attribute shape and
handler names.

For each annotated property, resolution uses this order:

1. Use a non-null `Autowired::$identifier` directly.
2. Otherwise infer one existing non-built-in class or interface from the property type.
3. If no identifier can be inferred, use the member property's declared default value.
4. If none applies, raise a property-resolution exception.

Once an explicit or inferred identifier exists, the resolver calls the container and does not fall back to the property
default when that entry is unavailable. Static properties are rejected. Reflection assignment supports public,
protected, private, and uninitialized readonly properties; an incompatible type or an already initialized readonly
property fails assignment.

`Autowired` has no lazy-loading switch. Supported properties receive a consumer-owned PHP 8.4 lazy proxy by default;
the referenced entry is not resolved until proxy state is observed or modified. The initializer then obtains the
ordinary canonical entry through `Container::get()`. A method that does not access proxy state may leave it uninitialized.
Initialization failure leaves the proxy retryable.

The proxy is not retained by the root and is not identical (`===`) to the canonical entry. A concrete class identifier
can be proxied directly; an interface or abstract identifier needs a `ProviderDefinition` that exposes a concrete
provider class. If there is no such class, including a `FactoryProvider`-only interface mapping, the handler performs
ordinary container lookup. It also uses ordinary lookup for internal classes other than `stdClass`, their subclasses,
and classes without stored instance properties. PHP does not invoke a proxy factory for objects containing only
static or virtual properties, so this fallback preserves constructors and canonical container identity.
Lazy initialization is synchronous and provides no single-flight or coroutine-local scope. Native proxies change
object identity and construction timing; deferred construction failures are observed on initialization.

## Resolution and concurrency

`ResolverFactory::resolve()` dispatches each definition directly through the priority queue and does not retain a
per-call resolution path. Circular dependency graphs are therefore not detected by the resolver factory and must be
excluded by application definitions.

The container does not serialize concurrent first resolution of an uncached identifier. Independent executions may
construct duplicate values; write-once retention keeps the first completed value canonical for later root lookups.

## Caller-owned direct resolution

Callers may obtain both canonical factories and resolve a top-level definition without retaining its top-level result:

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

This is caller ownership outside top-level retention, not a transient container scope. Dependencies requested during
that resolution still use the shared root and may be retained there.

## Development

Run component checks from `.vendor/di` with PHP 8.4.27 or later 8.4.x tooling:

```bash
composer84 validate --strict
composer84 test
composer84 format:check
```
