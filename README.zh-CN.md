<p align="right">
  <a href="README.md">English</a> | <strong>中文文档</strong>
</p>

# SuperKernel DI

`super-kernel/di` 是 SuperKernel 的 PSR-11 共享服务容器，并提供 `psr/container-implementation` 2.0。服务通过 `super-kernel/attribute` 中的原生 PHP 注解（Attribute）声明，在首次查找时解析，并在容器的整个生命周期内保留。

容器提供：

- 通过注解声明的接口映射和工厂提供者；
- 构造函数、方法和属性注入，属性默认使用延迟引用；
- 作为扩展点的自定义属性注解、定义器（definer）和解析器（resolver）；
- 不被容器保留的、由调用方持有的解析结果。

它不提供子容器、可变注册、瞬态作用域、编译容器、循环依赖检测或单次构造（single-flight）保证。

## 注册服务

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

- 在可实例化的类上标注 `#[Provider(Target::class)]`，会把 `Target` 映射到这个类。把类映射到自身，则将其注册为共享类。映射可以串联，例如 `interface -> interface -> concrete`，链上的每个标识都保留同一个实例。接口、抽象类或没有公开构造方式的类上的自映射会被拒绝，不兼容的映射和重复映射同样会被拒绝。
- `#[FactoryProvider(Target::class)]` 可以重复标注。工厂提供者必须可实例化，没有构造函数或只有公开的构造函数，并自己声明一个公开、非静态的 `__invoke()`，其参数在调用时注入。它用于自定义的构造、校验或初始化；普通构造就能满足时使用 `Provider`。
- 其他任何可实例化的类在被请求时直接构造。

## 构造函数和方法注入

每个构造函数或方法参数按以下顺序取值：

1. 通过 `MethodDefinition` 提供的值，先按参数名匹配，再按位置匹配；
2. 其唯一的类或接口类型对应的容器条目；
3. 其声明的默认值；
4. 参数可为 null 时取 `null`。

可变参数会被省略。引用传递的参数、有歧义的联合类型，以及以上来源都无法满足的必需参数，都会被拒绝。

## 属性注入

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

`#[Autowired]` 属性按以下顺序解析：

1. 非 null 的 `Autowired::$identifier`；
2. 其类型中唯一一个存在的非内置类或接口；
3. 其声明的默认值；
4. 以上都没有时，抛出属性解析错误。

属性在构造函数返回之后才赋值，因此构造函数中不能使用它们。一旦确定了标识，对应条目不可用就是错误，不会回退到默认值。支持 public、protected、private 以及尚未初始化的 readonly 属性；静态属性会被拒绝。方法上的 `#[Autowired]` 只是元数据，容器从不调用该方法。

受支持的属性会得到一个 PHP 8.4 惰性代理：第一次读取或写入代理的状态时，才通过 `Container::get()` 解析条目，初始化失败后可以重试。代理与规范条目并不相同（`===` 不成立）。接口或抽象标识只有在 `Provider` 映射到具体类时才会使用代理。以下情况改用普通查找：只有 `FactoryProvider` 的标识、除 `stdClass` 以外的内置类及其子类，以及没有实例存储属性的类。惰性初始化是同步的，没有单次构造保护。

## 自定义属性注解

一个包可以把自己的属性注解映射到一个处理器上，而不必依赖本包：

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

- `#[PropertyHandler(TheAttribute::class)]` 可以重复标注，所以一个处理器可以服务多个注解；每个注解最多只有一个处理器。不可实例化的处理器、不是注解的目标、不允许 `Attribute::TARGET_PROPERTY` 的目标以及重复映射都会被拒绝。
- 每个处理器都通过构造函数接收根容器。映射的注解按声明顺序处理；没有处理器的属性注解会被忽略。
- 不调用 `assign()` 时属性保持不变，`assign(null)` 赋值为 null，对同一个属性的第二次赋值会失败。

## 定义器与解析器

容器先用第一个支持该标识的定义器把标识转换为定义，再用第一个支持该定义的解析器解析定义。优先级高的先执行，优先级相同时保持发现顺序。

- `#[Definer(priority: N)]` 标注一个可实例化的 `SuperKernel\Di\Contract\DefinerInterface`。它没有必需的构造参数，或只需要 `AnnotationCollectorInterface`。内置定义器为 Provider `200`、FactoryProvider `100` 和 Class `0`。定义结果和不受支持的标识都会按标识缓存。
- `#[Resolver(priority: N)]` 标注一个 `SuperKernel\Di\Contract\ResolverInterface`，以 `SuperKernel\Contract\ContainerInterface` 构造。内置解析器为 Factory `400`、Provider `300`、Class `200`、Property `100` 和 Method `0`；高于 `400` 的优先级排在所有内置解析器之前。解析器把派生出的定义交给容器的 `ResolverFactoryInterface` 来完成嵌套工作，而不是直接调用另一个解析器。优先级决定由哪个解析器处理某个定义，并不决定构造函数注入或属性注入的顺序。 - 两个工厂都在首次使用时发现各自的定义器和解析器。如果某个扩展的构造函数可能让出协程，请在把容器交给并发工作之前先完成第一次查找。

## 创建容器

```php
use SuperKernel\Contract\AnnotationCollectorInterface;
use SuperKernel\Di\Container;

/** @var AnnotationCollectorInterface $annotationCollector */
$container = new Container($annotationCollector);
```

每个容器都保留以下条目，它们优先于传入的条目：

- `Container::class`、`SuperKernel\Contract\ContainerInterface` 和 `Psr\Container\ContainerInterface` 返回容器自身；
- `AnnotationCollectorInterface` 返回传入的收集器；
- `DefinitionFactoryInterface`、`DefinitionFactory::class`、`ResolverFactoryInterface` 和 `ResolverFactory::class` 返回容器自己的工厂。

`new Container($annotationCollector, $entries)` 还会保留传入的服务，包括非类标识和 null 值；类和接口标识必须对应兼容的实例。

`SuperKernel\Di\Provider\ContainerProvider` 是两个容器接口的工厂提供者。传入容器调用时，它原样返回该容器。不传容器调用时，它通过 `super-kernel/scan-isolation` 在隔离进程中扫描应用，恢复已发布的 Composer 缓存和注解缓存，并返回一个新的根容器。Process 和 Exec 策略会重新运行当前的 PHP 脚本。仅恢复的执行方式（包括 Phar）需要用部署的代码通过一次正常扫描生成缓存；缓存缺失或不兼容时会直接失败，不会隐式扫描。

启动后的根容器还会以接口和具体类为标识保留以下服务：`SuperKernel\Contract` 中的 `AnnotationCollectorInterface`、`BasePathInterface`、`PackageManagerInterface`、`ReflectorInterface` 和 `VendorDirInterface`，以及 `SuperKernel\Composer\Contract\ComposerMetadataInterface` 和 `SuperKernel\ScanIsolation\Contract\ScanProcessorInterface`。需要依赖目录时注入 `VendorDirInterface`；`PackageManagerInterface` 不提供它。

## 查找与错误

- 对于没有定义的标识，`get()` 抛出 PSR 的未找到异常；对于已知定义但没有解析器支持的情况，抛出 PSR 容器异常。构造函数和工厂抛出的 PSR 容器异常原样传出；其他失败会被包装，原始异常作为原因保留。
- `has()` 从不解析：它对已保留的条目和存在定义的标识返回 true，定义查找失败时返回 `false`。
- 对于类或接口标识，首次解析出的值必须是兼容的实例。一个标识首次保留的值永远不会被替换。
- 不检测循环依赖，因此不得声明循环依赖。同一个标识的并发首次查找可能构造出重复的实例；保留最先完成的那个值。

## 调用方持有的解析

解析一个定义，但不保留顶层结果：

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

这不是瞬态作用域：解析过程中请求的依赖仍来自共享容器，并可能被保留在其中。

## 扫描诊断

注解扫描跳过某些类时，启动过程会通过容器的 `StdoutLoggerInterface` 记录结果：

```text
Annotation scan skipped 1 class.
Annotation scan skipped %d classes.
Annotation scan skipped %s: %s: %s
```

前两条是 WARNING 级别的汇总；最后一条是每个被跳过的类各一条 INFO 记录，按捕获顺序输出，包含类名、异常类型和消息。ASCII 控制字节只在消息文本中转义。

- 汇总的 context：`event=annotation_scan.skipped_summary`、`skipped_count` 和 `diagnostic_source=annotation_cache`。
- 明细的 context：`event=annotation_scan.skipped_class`、`diagnostic_source`、`sequence`、`package`、`class`、`exception_type`，以及原始的 `exception_message`。

每个根容器只写一次报告：在执行扫描的子进程中写，或者在仅恢复的执行方式下由当前进程写。

| 扫描处理器  | 默认日志输出              | STDERR 回退     |
|-------------|---------------------------|-----------------|
| PCNTL       | 继承的 STDOUT             | 继承的 STDERR   |
| Process     | 父进程 STDOUT             | 父进程 STDERR   |
| Exec        | 父进程 STDERR（合并输出） | 父进程 STDERR   |
| Null / Phar | 当前进程 STDOUT           | 当前进程 STDERR |

- 没有绑定日志器时，报告以黄色写到 STDERR，启动继续。
- 如果日志器或它的某个依赖构造失败，或者写入失败，完整的报告会写到 STDERR，原始错误向外传播，启动停止；失败之前已写出的内容可能重复出现。
- 日志器的级别过滤会被遵守，不做回退。如果只想显示汇总，或者为了机器可读的输出保持 STDOUT 干净，请绑定一个阈值为 WARNING、或使用 STDERR 或文件处理器的应用日志器；参见 [Logger README](https://github.com/super-kernel/logger/blob/main/README.md#scan-diagnostics)。该日志器也会收到其他框架记录。DI 没有为这份报告提供详细程度开关或环境变量设置。
- 自定义日志器在执行扫描的子进程中构造。它的构造函数必须使用注入的服务，不得调用 `ApplicationContext::getContainer()`，也不得依赖应用启动。带缓冲或异步的传输必须考虑子进程会终止：PSR-3 调用正常返回只表示已接受，不表示已持久送达。
