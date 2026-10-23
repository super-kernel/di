<?php

declare(strict_types=1);

namespace SuperKernel\Di\Tests\Unit;

use Fiber;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface as PsrContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionClass;
use RuntimeException;
use stdClass;
use SuperKernel\Annotator\Model\AttributeMetadata;
use SuperKernel\Attribute\Definer;
use SuperKernel\Attribute\FactoryProvider;
use SuperKernel\Attribute\Provider;
use SuperKernel\Attribute\Resolver;
use SuperKernel\Contract\AnnotationCollectorInterface;
use SuperKernel\Contract\ContainerInterface;
use SuperKernel\Di\Container;
use SuperKernel\Di\Contract\DefinitionFactoryInterface;
use SuperKernel\Di\Contract\ResolverFactoryInterface;
use SuperKernel\Di\Contract\RetainedEntriesInterface;
use SuperKernel\Di\Exception\NotFoundException;
use SuperKernel\Di\Exception\ResolutionException;
use SuperKernel\Di\Factory\DefinitionFactory;
use SuperKernel\Di\Factory\ResolverFactory;
use SuperKernel\Di\Tests\Fixture\AmbiguousService;
use SuperKernel\Di\Tests\Fixture\AnnotatedProvider;
use SuperKernel\Di\Tests\Fixture\ContainerFactory;
use SuperKernel\Di\Tests\Fixture\DefaultedService;
use SuperKernel\Di\Tests\Fixture\Dependency;
use SuperKernel\Di\Tests\Fixture\FactoryProduct;
use SuperKernel\Di\Tests\Fixture\FirstResolver;
use SuperKernel\Di\Tests\Fixture\HighPriorityDefiner;
use SuperKernel\Di\Tests\Fixture\InterleavingDependency;
use SuperKernel\Di\Tests\Fixture\InterleavingRootService;
use SuperKernel\Di\Tests\Fixture\MissingContract;
use SuperKernel\Di\Tests\Fixture\NullableService;
use SuperKernel\Di\Tests\Fixture\Provider\MismatchedProvider;
use SuperKernel\Di\Tests\Fixture\ProviderContract;
use SuperKernel\Di\Tests\Fixture\ProviderSubcontract;
use SuperKernel\Di\Tests\Fixture\Service;
use SuperKernel\Di\Tests\Fixture\ThrowingDefiner;
use SuperKernel\Di\Tests\Fixture\ThrowingService;
use SuperKernel\Di\Tests\Fixture\UnresolvableService;
use Throwable;

use function sort;

final class ContainerTest extends TestCase
{
    /** @throws Throwable When fixture setup or the operation under test throws */
    public function testImplementsBothContainerContractsAndDeclaresOnlyExpectedMethods(): void
    {
        $reflection = new ReflectionClass(Container::class);
        $constructor = $reflection->getConstructor();
        $definitionFactory = $reflection->getProperty('definitionFactory');
        $retainedEntries = $reflection->getProperty('retainedEntries');
        $resolverFactory = $reflection->getProperty('resolverFactory');
        $declaredMethods = [];

        foreach ($reflection->getMethods() as $method) {
            if (Container::class !== $method->getDeclaringClass()->getName()) {
                continue;
            }

            $declaredMethods[] = $method->getName();
        }

        sort($declaredMethods);

        $this->assertTrue($reflection->implementsInterface(ContainerInterface::class));
        $this->assertTrue($reflection->implementsInterface(PsrContainerInterface::class));
        $this->assertTrue($reflection->isReadOnly());
        $this->assertNotNull($constructor);
        $this->assertSame(2, $constructor->getNumberOfParameters());
        $this->assertSame(1, $constructor->getNumberOfRequiredParameters());
        $this->assertSame('annotationCollector', $constructor->getParameters()[0]->getName());
        $this->assertSame(AnnotationCollectorInterface::class, (string) $constructor->getParameters()[0]->getType());
        $this->assertSame([], $constructor->getParameters()[1]->getDefaultValue());
        $this->assertSame([
            '__construct',
            'get',
            'has',
        ], $declaredMethods);
        $this->assertTrue($reflection->getMethod('get')->isPublic());
        $this->assertTrue($reflection->getMethod('has')->isPublic());
        $this->assertTrue($definitionFactory->isPrivate());
        $this->assertTrue($definitionFactory->isReadOnly());
        $this->assertSame(DefinitionFactoryInterface::class, (string) $definitionFactory->getType());
        $this->assertTrue($retainedEntries->isPrivate());
        $this->assertTrue($retainedEntries->isReadOnly());
        $this->assertSame(RetainedEntriesInterface::class, (string) $retainedEntries->getType());
        $this->assertTrue($resolverFactory->isPrivate());
        $this->assertTrue($resolverFactory->isReadOnly());
        $this->assertSame(ResolverFactoryInterface::class, (string) $resolverFactory->getType());
    }

    /** @throws Throwable When fixture setup or the operation under test throws */
    public function testAutowiresConstructorsAndRetainsResolvedObjects(): void
    {
        $container = $this->createContainer();
        $service = $container->get(Service::class);

        $this->assertInstanceOf(Service::class, $service);
        $this->assertSame($service, $container->get(Service::class));
        $this->assertSame($service->dependency, $container->get(Dependency::class));
        $this->assertSame('default', $container->get(DefaultedService::class)->name);
        $this->assertNull($container->get(NullableService::class)->dependency);
    }

    /** @throws Throwable When fixture setup or the operation under test throws */
    public function testRetainsSuppliedServicesAndNullWithoutReplacingReservedEntries(): void
    {
        $collector = ContainerFactory::collector();
        $dependency = new Dependency();
        $other = ContainerFactory::create();
        $container = new Container($collector, [
            Dependency::class                   => $dependency,
            'retained-null'                     => null,
            ContainerInterface::class           => $other,
            AnnotationCollectorInterface::class => ContainerFactory::collector(),
            DefinitionFactoryInterface::class   => $other->get(DefinitionFactoryInterface::class),
        ]);

        self::assertSame($dependency, $container->get(Dependency::class));
        self::assertSame($dependency, $container->get(Service::class)->dependency);
        self::assertTrue($container->has('retained-null'));
        self::assertNull($container->get('retained-null'));
        self::assertSame($container, $container->get(ContainerInterface::class));
        self::assertSame($collector, $container->get(AnnotationCollectorInterface::class));
        self::assertNotSame(
            $other->get(DefinitionFactoryInterface::class),
            $container->get(DefinitionFactoryInterface::class),
        );
    }

    public function testRejectsAnIncompatibleSuppliedService(): void
    {
        $this->expectException(ResolutionException::class);

        new Container(ContainerFactory::collector(), [
            Dependency::class => new stdClass(),
        ]);
    }

    /** @throws Throwable When fixture setup or the operation under test throws */
    public function testRejectsAResolvedValueThatDoesNotMatchItsClassIdentifier(): void
    {
        $container = ContainerFactory::create([
            MismatchedProvider::class => [
                new AttributeMetadata(new FactoryProvider(
                    FactoryProduct::class,
                )),
            ],
        ]);

        $this->expectException(ResolutionException::class);
        $this->expectExceptionMessageIsOrContains(
            'resolved to ' . Dependency::class . ' instead of an instance of ' . FactoryProduct::class,
        );

        $container->get(FactoryProduct::class);
    }

    /** @throws Throwable When fixture setup or the operation under test throws */
    public function testAllowsMixedValuesForIdentifiersThatAreNotClassNames(): void
    {
        $container = ContainerFactory::create([
            HighPriorityDefiner::class => [
                new AttributeMetadata(new Definer(
                    priority : 300,
                )),
            ],
            FirstResolver::class => [
                new AttributeMetadata(new Resolver(
                    priority : 10,
                )),
            ],
        ]);

        $this->assertSame('first', $container->get('service'));
        $this->assertSame('first', $container->get('service'));
    }

    /** @throws Throwable When fixture setup or the operation under test throws */
    public function testResolvesProviderDefinitionsAndRetainsTheProvider(): void
    {
        $container = ContainerFactory::create([
            ProviderSubcontract::class => [
                new AttributeMetadata(new Provider(
                    ProviderContract::class,
                )),
            ],
            AnnotatedProvider::class => [
                new AttributeMetadata(new Provider(
                    ProviderSubcontract::class,
                )),
            ],
        ]);
        $provider = $container->get(ProviderContract::class);

        $this->assertInstanceOf(AnnotatedProvider::class, $provider);
        $this->assertSame($provider, $container->get(ProviderContract::class));
        $this->assertSame($provider, $container->get(ProviderSubcontract::class));
        $this->assertSame($provider, $container->get(AnnotatedProvider::class));
    }

    /** @throws Throwable When fixture setup or the operation under test throws */
    public function testExposesReservedContainerAndFactoryEntries(): void
    {
        $annotationCollector = ContainerFactory::collector();
        $container = new Container($annotationCollector);
        $definitionFactory = $container->get(DefinitionFactoryInterface::class);
        $resolverFactory = $container->get(ResolverFactoryInterface::class);

        $this->assertSame($container, $container->get(Container::class));
        $this->assertSame($container, $container->get(ContainerInterface::class));
        $this->assertSame($container, $container->get(PsrContainerInterface::class));
        $this->assertSame($annotationCollector, $container->get(AnnotationCollectorInterface::class));
        $this->assertInstanceOf(DefinitionFactory::class, $definitionFactory);
        $this->assertSame($definitionFactory, $container->get(DefinitionFactoryInterface::class));
        $this->assertSame($definitionFactory, $container->get(DefinitionFactory::class));
        $this->assertInstanceOf(ResolverFactory::class, $resolverFactory);
        $this->assertSame($resolverFactory, $container->get(ResolverFactoryInterface::class));
        $this->assertSame($resolverFactory, $container->get(ResolverFactory::class));
    }

    /** @throws Throwable When fixture setup or the operation under test throws */
    public function testRetrievedFactoriesResolveCallerOwnedTopLevelInstancesWithoutRetainingThem(): void
    {
        $container = $this->createContainer();
        $definitionFactory = $container->get(DefinitionFactoryInterface::class);
        $resolverFactory = $container->get(ResolverFactoryInterface::class);

        $this->assertInstanceOf(DefinitionFactoryInterface::class, $definitionFactory);
        $this->assertInstanceOf(ResolverFactoryInterface::class, $resolverFactory);

        $definition = $definitionFactory->get(Service::class);

        $this->assertNotNull($definition);

        $first = $resolverFactory->resolve($definition);
        $second = $resolverFactory->resolve($definition);

        $this->assertInstanceOf(Service::class, $first);
        $this->assertInstanceOf(Service::class, $second);
        $this->assertNotSame($first, $second);
        $this->assertSame($first->dependency, $second->dependency);

        $retained = $container->get(Service::class);

        $this->assertInstanceOf(Service::class, $retained);
        $this->assertNotSame($first, $retained);
        $this->assertNotSame($second, $retained);
        $this->assertSame($retained, $container->get(Service::class));
        $this->assertSame($first->dependency, $retained->dependency);
    }

    public function testHasReportsDefinitionsWithoutResolvingThem(): void
    {
        HighPriorityDefiner::$supportCalls = 0;
        HighPriorityDefiner::$getCalls = 0;
        $container = ContainerFactory::create([
            HighPriorityDefiner::class => [
                new AttributeMetadata(new Definer(
                    priority : 300,
                )),
            ],
        ]);

        $this->assertTrue($container->has('service'));
        $this->assertFalse($container->has('missing'));
        $this->assertSame(2, HighPriorityDefiner::$supportCalls);
        $this->assertSame(1, HighPriorityDefiner::$getCalls);
    }

    public function testDefaultClassDefinerReportsOnlyInstantiableClassesWithoutResolvingThem(): void
    {
        $container = $this->createContainer();

        $this->assertTrue($container->has(Service::class));
        $this->assertTrue($container->has(ThrowingService::class));
        $this->assertFalse($container->has(MissingContract::class));
        $this->assertFalse($container->has('missing'));
    }

    /** @throws Throwable When fixture setup or the operation under test throws */
    public function testHasConvertsDefinitionFactoryFailuresToFalseWhileGetPropagatesThem(): void
    {
        $container = ContainerFactory::create([
            ThrowingDefiner::class => [
                new AttributeMetadata(new Definer(
                    priority : 300,
                )),
            ],
        ]);

        $this->assertFalse($container->has('broken'));

        try {
            $container->get('broken');
            $this->fail('Expected a resolution exception.');
        } catch (ResolutionException $exception) {
            $this->assertSame('definition lookup failure', $exception->getPrevious()?->getMessage());
        }
    }

    /**
     * These assertions protect the public interface contract even when static types already imply it.
     *
     * @throws Throwable When fixture setup or the operation under test throws
     *
     * @noinspection PhpConditionAlreadyCheckedInspection
     */
    public function testUnknownIdentifierRaisesThePsrNotFoundContract(): void
    {
        $container = $this->createContainer();

        try {
            $container->get('missing');
            $this->fail('Expected a not-found exception.');
        } catch (NotFoundExceptionInterface $exception) {
            $this->assertInstanceOf(ContainerExceptionInterface::class, $exception);
            $this->assertInstanceOf(NotFoundException::class, $exception);
            $this->assertSame('missing', $exception->identifier());
            $this->assertSame('Container entry "missing" has no definition.', $exception->getMessage());
        }
    }

    /** @throws Throwable When fixture setup or the operation under test throws */
    public function testUnresolvableRequiredParameterRaisesAContainerException(): void
    {
        $this->expectException(ResolutionException::class);
        $this->expectExceptionMessageIsOrContains('UnresolvableService::__construct($port)');

        $this->createContainer()->get(UnresolvableService::class);
    }

    /** @throws Throwable When fixture setup or the operation under test throws */
    public function testAmbiguousUnionRaisesAContainerException(): void
    {
        $this->expectException(ResolutionException::class);
        $this->expectExceptionMessageIsOrContains('AmbiguousService::__construct($dependency) is ambiguous');

        $this->createContainer()->get(AmbiguousService::class);
    }

    /** @throws Throwable When fixture setup or the operation under test throws */
    public function testKeepsInterleavedFirstResolutionsIndependent(): void
    {
        InterleavingDependency::$constructions = 0;
        $container = $this->createContainer();
        $rootFiber = new Fiber(
            static fn(): mixed => $container->get(InterleavingRootService::class),
        );
        $dependencyFiber = new Fiber(
            static fn(): mixed => $container->get(InterleavingDependency::class),
        );

        $this->assertSame('root', $rootFiber->start());
        $this->assertSame('dependency', $dependencyFiber->start());

        $rootFiber->resume();

        $this->assertTrue($rootFiber->isTerminated());
        $rootService = $rootFiber->getReturn();
        $this->assertInstanceOf(InterleavingRootService::class, $rootService);

        $dependencyFiber->resume();

        $this->assertTrue($dependencyFiber->isTerminated());
        $this->assertSame(2, InterleavingDependency::$constructions);
        $this->assertSame($rootService->dependency, $dependencyFiber->getReturn());
        $this->assertSame($rootService, $container->get(InterleavingRootService::class));
        $this->assertSame($rootService->dependency, $container->get(InterleavingDependency::class));
    }

    /** @throws Throwable When fixture setup or the operation under test throws */
    public function testConstructorFailureIsWrappedWithItsCause(): void
    {
        try {
            $this->createContainer()->get(ThrowingService::class);
            $this->fail('Expected a resolution exception.');
        } catch (ResolutionException $exception) {
            $this->assertInstanceOf(RuntimeException::class, $exception->getPrevious());
            $this->assertSame('constructor failure', $exception->getPrevious()->getMessage());
        }
    }

    /** @throws Throwable When fixture setup or the operation under test throws */
    public function testKnownDefinitionWithoutBuiltInResolverSupportRaisesAContainerException(): void
    {
        $container = ContainerFactory::create([
            HighPriorityDefiner::class => [
                new AttributeMetadata(new Definer(
                    priority : 300,
                )),
            ],
        ]);

        $this->assertTrue($container->has('service'));
        $this->expectException(ResolutionException::class);
        $this->expectExceptionMessageIsOrContains('No resolver supports definition');

        $container->get('service');
    }

    private function createContainer(): Container
    {
        return ContainerFactory::create();
    }
}
