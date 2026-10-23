<?php

declare(strict_types=1);

namespace SuperKernel\Di\Tests\Unit;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface as PsrContainerInterface;
use ReflectionClass;
use RuntimeException;
use SplPriorityQueue;
use SuperKernel\Annotator\Model\AttributeMetadata;
use SuperKernel\Attribute\FactoryProvider;
use SuperKernel\Attribute\Resolver;
use SuperKernel\Contract\AnnotationCollectorInterface;
use SuperKernel\Contract\AttributeMetadataInterface;
use SuperKernel\Contract\ContainerInterface;
use SuperKernel\Di\Container;
use SuperKernel\Di\Contract\DefinitionInterface;
use SuperKernel\Di\Contract\ResolverFactoryInterface;
use SuperKernel\Di\Definition\ClassDefinition;
use SuperKernel\Di\Definition\FactoryDefinition;
use SuperKernel\Di\Definition\PropertyDefinition;
use SuperKernel\Di\Exception\ResolutionException;
use SuperKernel\Di\Factory\ResolverFactory;
use SuperKernel\Di\Resolver\ClassResolver;
use SuperKernel\Di\Resolver\FactoryResolver;
use SuperKernel\Di\Resolver\MethodResolver;
use SuperKernel\Di\Resolver\PropertyResolver;
use SuperKernel\Di\Resolver\ProviderResolver;
use SuperKernel\Di\Tests\Fixture\ContainerFactory;
use SuperKernel\Di\Tests\Fixture\Dependency;
use SuperKernel\Di\Tests\Fixture\FactoryProduct;
use SuperKernel\Di\Tests\Fixture\FirstResolver;
use SuperKernel\Di\Tests\Fixture\MethodInjectedProduct;
use SuperKernel\Di\Tests\Fixture\NamedDefinition;
use SuperKernel\Di\Tests\Fixture\Provider\AnnotatedProvider;
use SuperKernel\Di\Tests\Fixture\Provider\MethodInjectedProvider;
use SuperKernel\Di\Tests\Fixture\SecondResolver;
use SuperKernel\Di\Tests\Fixture\Service;
use SuperKernel\Di\Tests\Fixture\ThrowingService;
use Throwable;

use function array_map;
use function iterator_to_array;

final class ResolverFactoryTest extends TestCase
{
    /** @throws Throwable When a resolver or its metadata cannot be resolved */
    public function testOrdersResolversUsingIndependentMetadata(): void
    {
        $metadata = $this->createStub(AttributeMetadataInterface::class);
        $metadata->method('attribute')->willReturn(new Resolver(priority: 20));
        $collector = $this->createMock(AnnotationCollectorInterface::class);
        $collector->expects($this->once())->method('getClassesWithAttribute')->with(Resolver::class)->willReturn([
            FirstResolver::class => [
                new AttributeMetadata(new Resolver(priority: 10)),
            ],
            SecondResolver::class => [
                $metadata,
            ],
        ]);
        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->once())->method('get')
            ->with(AnnotationCollectorInterface::class)->willReturn($collector);
        $factory = new ResolverFactory($container);
        $definition = new NamedDefinition('custom');

        self::assertTrue($factory->support($definition));
        self::assertSame('second', $factory->resolve($definition));
        self::assertSame('second', $factory->resolve($definition));
    }

    /** @throws Throwable When fixture setup or the operation under test throws */
    public function testFactoryIsContainerBoundAndDiscoversTheBuiltInResolvers(): void
    {
        $container = $this->createContainer();
        $factory = $container->get(ResolverFactoryInterface::class);
        $reflection = new ReflectionClass(ResolverFactory::class);
        $constructor = $reflection->getConstructor();
        $support = $reflection->getMethod('support');
        $resolve = $reflection->getMethod('resolve');
        $resolvers = $reflection->getProperty('resolvers');
        $boundContainer = $reflection->getProperty('container');

        $this->assertInstanceOf(ResolverFactory::class, $factory);
        $this->assertFalse($reflection->isReadOnly());
        $this->assertNotNull($constructor);
        $this->assertSame(1, $constructor->getNumberOfRequiredParameters());
        $this->assertSame(PsrContainerInterface::class, (string) $constructor->getParameters()[0]->getType());
        $this->assertSame(1, $support->getNumberOfRequiredParameters());
        $this->assertSame('definition', $support->getParameters()[0]->getName());
        $this->assertSame(1, $resolve->getNumberOfRequiredParameters());
        $this->assertSame('definition', $resolve->getParameters()[0]->getName());
        $this->assertSame(DefinitionInterface::class, (string) $resolve->getParameters()[0]->getType());
        $this->assertTrue($resolvers->isPrivate());
        $this->assertFalse($resolvers->isReadOnly());
        $this->assertSame(SplPriorityQueue::class, (string) $resolvers->getType());
        $this->assertArrayHasKey('get', $resolvers->getHooks());
        $this->assertFalse($resolvers->isInitialized($factory));
        $this->assertTrue($factory->support(new ClassDefinition(Service::class)));
        $this->assertTrue($factory->support(new PropertyDefinition(Service::class)));
        $discovered = $resolvers->getRawValue($factory);
        $this->assertInstanceOf(SplPriorityQueue::class, $discovered);
        $this->assertCount(5, $discovered);
        $this->assertSame(
            [
                FactoryResolver::class,
                ProviderResolver::class,
                ClassResolver::class,
                PropertyResolver::class,
                MethodResolver::class,
            ],
            array_map(
                static fn(object $resolver): string => $resolver::class,
                iterator_to_array(clone $discovered, false),
            ),
        );
        $this->assertTrue($factory->support(new ClassDefinition(Service::class)));
        $this->assertSame($discovered, $resolvers->getRawValue($factory));
        $this->assertCount(5, $discovered);
        $this->assertTrue($boundContainer->isPrivate());
        $this->assertTrue($boundContainer->isReadOnly());
        $this->assertSame($container, $boundContainer->getValue($factory));

        foreach (clone $discovered as $resolver) {
            $dispatcher = new ReflectionClass($resolver)->getProperty('container');

            $this->assertSame($container, $dispatcher->getValue($resolver));
        }
    }

    /** @throws Throwable When fixture setup or the operation under test throws */
    public function testResolvesFactoryDefinitionsAndRetainsContainerLookups(): void
    {
        $definition = new FactoryDefinition(FactoryProduct::class, AnnotatedProvider::class);
        $container = ContainerFactory::create([
            AnnotatedProvider::class => [
                new AttributeMetadata(new FactoryProvider(
                    FactoryProduct::class,
                )),
            ],
        ]);
        $factory = $container->get(ResolverFactoryInterface::class);

        $this->assertInstanceOf(ResolverFactoryInterface::class, $factory);
        $this->assertTrue($factory->support($definition));

        $firstCallerOwned = $factory->resolve($definition);
        $secondCallerOwned = $factory->resolve($definition);

        $this->assertInstanceOf(FactoryProduct::class, $firstCallerOwned);
        $this->assertInstanceOf(FactoryProduct::class, $secondCallerOwned);
        $this->assertNotSame($firstCallerOwned, $secondCallerOwned);

        $retained = $container->get(FactoryProduct::class);

        $this->assertInstanceOf(FactoryProduct::class, $retained);
        $this->assertNotSame($firstCallerOwned, $retained);
        $this->assertNotSame($secondCallerOwned, $retained);
        $this->assertSame($retained, $container->get(FactoryProduct::class));
    }

    /** @throws Throwable When fixture setup or the operation under test throws */
    public function testUsesTheHighestPrioritySupportingResolver(): void
    {
        $container = ContainerFactory::create([
            FirstResolver::class => [
                new AttributeMetadata(new Resolver(
                    priority : 10,
                )),
            ],
            SecondResolver::class => [
                new AttributeMetadata(new Resolver(
                    priority : 20,
                )),
            ],
        ]);
        $factory = $container->get(ResolverFactoryInterface::class);

        $this->assertInstanceOf(ResolverFactoryInterface::class, $factory);
        $this->assertSame('second', $factory->resolve(new NamedDefinition('service')));
    }

    /** @throws Throwable When fixture setup or the operation under test throws */
    public function testPreservesResolverDiscoveryOrderWhenPrioritiesAreEqual(): void
    {
        $container = ContainerFactory::create([
            FirstResolver::class => [
                new AttributeMetadata(new Resolver(
                    priority : 10,
                )),
            ],
            SecondResolver::class => [
                new AttributeMetadata(new Resolver(
                    priority : 10,
                )),
            ],
        ]);
        $factory = $container->get(ResolverFactoryInterface::class);

        $this->assertInstanceOf(ResolverFactoryInterface::class, $factory);
        $this->assertSame('first', $factory->resolve(new NamedDefinition('service')));
    }

    /** @throws Throwable When fixture setup or the operation under test throws */
    public function testRejectsAnnotatedClassWithoutTheResolverContract(): void
    {
        $container = ContainerFactory::create([
            Dependency::class => [
                new AttributeMetadata(new Resolver()),
            ],
        ]);
        $factory = $container->get(ResolverFactoryInterface::class);

        $this->assertInstanceOf(ResolverFactoryInterface::class, $factory);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIsOrContains('must implement');

        $factory->support(new ClassDefinition(Service::class));
    }

    /** @throws Throwable When fixture setup or the operation under test throws */
    public function testResolvesFactoryConstructorAndInvokeDependenciesThroughTheContainer(): void
    {
        $container = ContainerFactory::create([
            MethodInjectedProvider::class => [
                new AttributeMetadata(new FactoryProvider(
                    MethodInjectedProduct::class,
                )),
            ],
        ]);

        $product = $container->get(MethodInjectedProduct::class);
        $factory = $container->get(MethodInjectedProvider::class);

        $this->assertInstanceOf(MethodInjectedProduct::class, $product);
        $this->assertInstanceOf(MethodInjectedProvider::class, $factory);
        $this->assertSame($factory->constructorDependency, $product->constructorDependency);
        $this->assertSame($container->get(Service::class), $product->methodDependency);
        $this->assertSame($product->constructorDependency, $product->methodDependency->dependency);
    }

    /** @throws Throwable When fixture setup or the operation under test throws */
    public function testPropagatesFactoryInvocationFailureWithItsCause(): void
    {
        $throwingFactory = new class() {
            public function __invoke(): never
            {
                throw new RuntimeException('factory invocation failure');
            }
        };
        $container = $this->createContainer();
        $factory = $container->get(ResolverFactoryInterface::class);

        $this->assertInstanceOf(ResolverFactoryInterface::class, $factory);

        try {
            $factory->resolve(new FactoryDefinition(FactoryProduct::class, $throwingFactory::class));
            $this->fail('Expected a factory invocation exception.');
        } catch (ResolutionException $exception) {
            $this->assertStringContainsString('Unable to invoke factory', $exception->getMessage());
            $this->assertInstanceOf(RuntimeException::class, $exception->getPrevious());
            $this->assertSame('factory invocation failure', $exception->getPrevious()->getMessage());
        }
    }

    /** @throws Throwable When fixture setup or the operation under test throws */
    public function testCreatesCallerOwnedTopLevelInstancesWithRootScopedDependencies(): void
    {
        $container = $this->createContainer();
        $factory = $container->get(ResolverFactoryInterface::class);
        $definition = new ClassDefinition(Service::class);

        $this->assertInstanceOf(ResolverFactoryInterface::class, $factory);
        $this->assertTrue($factory->support($definition));
        $this->assertSame(Service::class, $definition->identifier());

        $first = $factory->resolve($definition);
        $second = $factory->resolve($definition);

        $this->assertInstanceOf(Service::class, $first);
        $this->assertInstanceOf(Service::class, $second);
        $this->assertNotSame($first, $second);
        $this->assertSame($first->dependency, $second->dependency);
        $this->assertSame($first->dependency, $container->get(Dependency::class));
    }

    /** @throws Throwable When fixture setup or the operation under test throws */
    public function testThrowsWhenBuiltInResolverDoesNotSupportTheDefinition(): void
    {
        $container = $this->createContainer();
        $factory = $container->get(ResolverFactoryInterface::class);
        $definition = new NamedDefinition('service');

        $this->assertInstanceOf(ResolverFactoryInterface::class, $factory);
        $this->assertFalse($factory->support($definition));
        $this->expectException(ResolutionException::class);
        $this->expectExceptionMessageIsOrContains('No resolver supports definition');

        $factory->resolve($definition);
    }

    /** @throws Throwable When fixture setup or the operation under test throws */
    public function testPropagatesBuiltInResolverConstructionFailureWithItsCause(): void
    {
        $container = $this->createContainer();
        $factory = $container->get(ResolverFactoryInterface::class);

        $this->assertInstanceOf(ResolverFactoryInterface::class, $factory);

        try {
            $factory->resolve(new ClassDefinition(ThrowingService::class));
            $this->fail('Expected a resolution exception.');
        } catch (ResolutionException $exception) {
            $this->assertInstanceOf(RuntimeException::class, $exception->getPrevious());
            $this->assertSame('constructor failure', $exception->getPrevious()->getMessage());
        }
    }

    private function createContainer(): Container
    {
        return ContainerFactory::create();
    }
}
