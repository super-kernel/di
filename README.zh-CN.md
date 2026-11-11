<p align="right">
  <a href="README.md">English</a> | <strong>中文文档</strong>
</p>

# SuperKernel DI

`super-kernel/di` 是 SuperKernel 的 PSR-11 容器：它创建类用注解声明的服务，注入它们的依赖，并在容器的整个生命周期内共享每个服务。

## 安装

```bash
composer require super-kernel/di
```

本包提供 `psr/container-implementation` 2.0。容器读取 `skernel build` 生成的元数据，因此项目需要用 SKERNEL 构建。

## 提供的能力

- 用 `#[Provider]` 和 `#[FactoryProvider]` 注解声明的服务，在首次查找时创建，之后共享。
- 按类或接口类型进行构造函数注入和工厂注入，无需配置。
- 用 `#[Autowired]` 进行属性注入，默认注入延迟引用。
- 作为扩展点的自定义属性注解、定义器（definer）和解析器（resolver）。
- 从 `skernel build` 的元数据恢复根容器，启动时不扫描。
- 当调用方自己持有对象时，在容器的共享之外解析一个新对象。

它不提供：

- 子容器、作用域或按请求的生命周期；
- 运行时注册或替换条目；
- 编译后的容器；
- 循环依赖检测；
- 防止两个协程同时创建同一个条目的保护。

## 快速开始

声明一个接口以及提供它的类。将接口保存为 `src/Greeting/Greeter.php`：

```php
<?php

declare(strict_types=1);

namespace Src\Greeting;

interface Greeter
{
    public function greet(string $name): string;
}
```

将类保存为 `src/Greeting/EnglishGreeter.php`：

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

恢复容器并查找该服务。将脚本保存为 `bin/greet.php`：

```php
<?php

declare(strict_types=1);

use Src\Greeting\Greeter;
use SuperKernel\Di\Provider\ContainerProvider;

require dirname(__DIR__) . '/vendor/autoload.php';

$container = new ContainerProvider()();

echo $container->get(Greeter::class)->greet('SuperKernel'), PHP_EOL;
```

构建项目并运行脚本：

```bash
skernel build
php bin/greet.php
```

它会打印 `Hello, SuperKernel!`。

## 公开接口

| 名称 | 类别 | 用途 |
| --- | --- | --- |
| `SuperKernel\Attribute\Provider` | 注解 | 把一个标识符映射到被注解的类；映射到自身的类成为共享类。 |
| `SuperKernel\Attribute\FactoryProvider` | 注解 | 通过调用被注解类的 `__invoke()` 创建一个标识符，其参数会被注入。 |
| `SuperKernel\Attribute\Autowired` | 注解 | 在构造函数返回之后注入一个属性。 |
| `SuperKernel\Attribute\PropertyHandler` | 注解 | 注册自定义属性注解的处理器。 |
| `SuperKernel\Contract\PropertyAttributeHandlerInterface` | 接口 | 由属性处理器实现。 |
| `SuperKernel\Contract\PropertyContextInterface` | 接口 | 传给属性处理器，用于查看属性并为其赋值。 |
| `SuperKernel\Attribute\Definer` | 注解 | 以某个优先级注册一个定义器。 |
| `SuperKernel\Di\Contract\DefinerInterface` | 接口 | 由定义器实现，定义器把标识符转换为定义。 |
| `SuperKernel\Di\Contract\DefinitionInterface` | 接口 | 由定义实现。 |
| `SuperKernel\Attribute\Resolver` | 注解 | 以某个优先级注册一个解析器。 |
| `SuperKernel\Di\Contract\ResolverInterface` | 接口 | 由解析器实现，解析器把定义转换为值。 |
| `SuperKernel\Di\Contract\DefinitionFactoryInterface` | 接口 | 从容器获取一个标识符的定义。 |
| `SuperKernel\Di\Contract\ResolverFactoryInterface` | 接口 | 解析一个定义而不共享其结果。 |
| `SuperKernel\Di\Container` | 类 | 容器；`new Container($collector, $entries)` 用注解收集器创建一个容器。 |
| `SuperKernel\Di\Provider\ContainerProvider` | 类 | 从 `skernel build` 的元数据恢复根容器。 |

## 需要了解的行为

- 在 `composer install` 或 `composer update` 之后，以及修改自动加载规则或带注解的类之后，请运行 `skernel build`，或者用会先构建的 `skernel run` 启动。元数据缺失或过期时，`ContainerProvider` 会抛出 `SuperKernel\Metadata\Exception\BuildRequiredException`，其消息以 ``Run `skernel build` to generate the project metadata.`` 结尾。修改带注解的类不会被检测到；容器会继续使用上一次构建的元数据。
- 对于没有定义的标识符，`get()` 抛出 PSR-11 未找到异常 `SuperKernel\Di\Exception\NotFoundException`；其他所有失败都抛出 PSR-11 容器异常 `SuperKernel\Di\Exception\ResolutionException`，原始异常作为其前一个异常（previous）。你的构造函数和工厂抛出的 PSR-11 异常会原样传出。
- 无效的声明（例如同一个标识符有两个提供者）在第一次需要它的查找时报告，而不是由 `skernel build` 报告。同一个标识符有两个提供者时，所有尚未创建的条目的查找都会失败。
- `has()` 从不创建条目：它报告一个标识符是否已被保留或具有定义；获取定义失败时报告 `false`。
- 为一个标识符创建的第一个值会在容器的整个生命周期内保留；类或接口标识符必须解析为该类型的实例。
- 属性在构造函数返回之后才注入，因此构造函数不能使用它。延迟属性持有的是一个代理，它与容器中的条目并不全等（`===`）。
- 循环依赖会以致命错误结束进程。

## 文档

开发者文档用完整示例说明每一项能力、各种错误及其消息，以及不要这样做的事情：[SuperKernel DI](https://github.com/super-kernel/.github/blob/main/docs/components/di.zh-CN.md)。

## 许可证

基于 MIT 许可证发布，详见 [LICENSE](LICENSE)。
