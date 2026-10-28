<?php

declare(strict_types=1);

namespace SuperKernel\Di\Tests\Unit;

use DateTimeImmutable;
use Error;
use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\TestCase;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionClass;
use RuntimeException;
use stdClass;
use SuperKernel;
use SuperKernel\Annotator\Model\AttributeMetadata;
use SuperKernel\Attribute\Autowired;
use SuperKernel\Attribute\FactoryProvider;
use SuperKernel\Attribute\PropertyHandler;
use SuperKernel\Attribute\Provider;
use SuperKernel\Di\Container;
use SuperKernel\Di\Definition\ClassDefinition;
use SuperKernel\Di\Definition\PropertyDefinition;
use SuperKernel\Di\Exception\ResolutionException;
use SuperKernel\Di\Resolver\PropertyResolver;
use SuperKernel\Di\Tests\Fixture\AnnotatedProvider;
use SuperKernel\Di\Tests\Fixture\ContainerFactory;
use SuperKernel\Di\Tests\Fixture\Dependency;
use SuperKernel\Di\Tests\Fixture\InternalDateTime;
use SuperKernel\Di\Tests\Fixture\LazyAnnotatedProvider;
use SuperKernel\Di\Tests\Fixture\LazyDependency;
use SuperKernel\Di\Tests\Fixture\LazyPropertyService;
use SuperKernel\Di\Tests\Fixture\LazyProviderPropertyService;
use SuperKernel\Di\Tests\Fixture\LazyThrowingPropertyService;
use SuperKernel\Di\Tests\Fixture\LazyThrowingService;
use SuperKernel\Di\Tests\Fixture\MismatchedPropertyService;
use SuperKernel\Di\Tests\Fixture\MissingProviderPropertyService;
use SuperKernel\Di\Tests\Fixture\PropertyInjectedService;
use SuperKernel\Di\Tests\Fixture\ProviderContract;
use SuperKernel\Di\Tests\Fixture\ProviderSubcontract;
use SuperKernel\Di\Tests\Fixture\Service;
use SuperKernel\Di\Tests\Fixture\StatelessDependency;
use SuperKernel\Di\Tests\Fixture\StaticInjectedService;
use SuperKernel\Di\Tests\Fixture\ThirdPartyPropertyAttribute;
use SuperKernel\Di\Tests\Fixture\ThirdPartyPropertyHandler;
use SuperKernel\Di\Tests\Fixture\ThirdPartyPropertyService;
use SuperKernel\Di\Tests\Fixture\UnresolvablePropertyService;
use Throwable;
use TypeError;

use function array_keys;

final class PropertyResolverTest extends TestCase
{
    public function testDefinitionCarriesOnlyTheClassName(): void
    {
        $definition = new PropertyDefinition(PropertyInjectedService::class);
        $resolver = new PropertyResolver(ContainerFactory::create());

        $this->assertSame(PropertyInjectedService::class, $definition->className);
        $this->assertStringContainsString(PropertyInjectedService::class, $definition->identifier());
        $this->assertTrue($resolver->support($definition));
        $this->assertFalse($resolver->support(new ClassDefinition(PropertyInjectedService::class)));
    }

    /** @throws Throwable When fixture setup or the operation under test throws */
    public function testResolvesAnnotatedPropertiesInReflectionOrderByPropertyName(): void
    {
        $container = ContainerFactory::create();
        $values = new PropertyResolver($container)->resolve(new PropertyDefinition(PropertyInjectedService::class));

        $this->assertSame([
            'dependency',
            'provider',
            'name',
            'fallback',
        ], array_keys($values));
        $this->assertSame($container->get(Dependency::class), $values['dependency']);
        $this->assertSame($container->get(AnnotatedProvider::class), $values['provider']);
        $this->assertSame('default', $values['name']);
        $this->assertNull($values['fallback']);
        $this->assertSame([], new PropertyResolver($container)->resolve(new PropertyDefinition(Service::class)));
    }

    /** @throws Throwable When fixture setup or the operation under test throws */
    public function testReturnsAnEmptyMapWhenTheClassHasNoProperties(): void
    {
        $values = new PropertyResolver(ContainerFactory::create())->resolve(new PropertyDefinition(stdClass::class));

        $this->assertSame([], $values);
    }

    /** @throws Throwable When fixture setup or the operation under test throws */
    public function testClassResolutionInjectsPrivateReadonlyProtectedAndDefaultedProperties(): void
    {
        $container = ContainerFactory::create();
        $service = $container->get(PropertyInjectedService::class);

        $this->assertInstanceOf(PropertyInjectedService::class, $service);
        $this->assertSame($container->get(Dependency::class), $service->dependency());
        $this->assertSame($container->get(AnnotatedProvider::class), $service->provider());
        $this->assertSame('default', $service->name);
        $this->assertNull($service->fallback);
    }

    /** @throws Throwable When fixture setup or the operation under test throws */
    public function testDispatchesThirdPartyPropertyAttributesAndPreservesExplicitSkip(): void
    {
        $container = ContainerFactory::create([
            ThirdPartyPropertyHandler::class => [
                new AttributeMetadata(new PropertyHandler(
                    ThirdPartyPropertyAttribute::class,
                )),
            ],
        ]);
        $values = new PropertyResolver($container)->resolve(new PropertyDefinition(ThirdPartyPropertyService::class));

        $this->assertSame([
            'handled',
            'nullable',
        ], array_keys($values));
        $this->assertSame('handled', $values['handled']);
        $this->assertNull($values['nullable']);
        $this->assertArrayNotHasKey('untouched', $values);

        $service = $container->get(ThirdPartyPropertyService::class);

        $this->assertSame('handled', $service->handled);
        $this->assertNull($service->nullable);
        $this->assertSame('default', $service->untouched);
    }

    /** @throws Throwable When fixture setup or the operation under test throws */
    public function testRejectsMultipleHandlerAssignmentsToOneProperty(): void
    {
        $consumer = new class() {
            #[ThirdPartyPropertyAttribute('first')]
            #[ThirdPartyPropertyAttribute('second')]
            public string $value;
        };
        $container = ContainerFactory::create([
            ThirdPartyPropertyHandler::class => [
                new AttributeMetadata(new PropertyHandler(
                    ThirdPartyPropertyAttribute::class,
                )),
            ],
        ]);

        try {
            new PropertyResolver($container)->resolve(new PropertyDefinition($consumer::class));
            $this->fail('Expected competing property assignments to fail.');
        } catch (ResolutionException $exception) {
            $this->assertInstanceOf(LogicException::class, $exception->getPrevious());
            $this->assertStringContainsString('Property handler', $exception->getMessage());
        }
    }

    /** @throws Throwable When fixture setup or the operation under test throws */
    public function testRejectsDuplicatePropertyHandlerMappings(): void
    {
        $container = ContainerFactory::create([
            ThirdPartyPropertyHandler::class => [
                new AttributeMetadata(new PropertyHandler(
                    Autowired::class,
                )),
            ],
        ]);

        try {
            new PropertyResolver($container)->resolve(new PropertyDefinition(PropertyInjectedService::class));
            $this->fail('Expected duplicate property handler mappings to fail.');
        } catch (ResolutionException $exception) {
            $this->assertInstanceOf(InvalidArgumentException::class, $exception->getPrevious());
            $this->assertStringContainsString('handled by both', $exception->getPrevious()->getMessage());
        }
    }

    /** @throws Throwable When fixture setup or the operation under test throws */
    public function testRejectsAPropertyHandlerThatDoesNotImplementTheSharedContract(): void
    {
        $container = ContainerFactory::create([
            Dependency::class => [
                new AttributeMetadata(new PropertyHandler(
                    ThirdPartyPropertyAttribute::class,
                )),
            ],
        ]);

        try {
            new PropertyResolver($container)->resolve(new PropertyDefinition(ThirdPartyPropertyService::class));
            $this->fail('Expected an invalid property handler implementation to fail.');
        } catch (ResolutionException $exception) {
            $this->assertInstanceOf(InvalidArgumentException::class, $exception->getPrevious());
            $this->assertStringContainsString('instantiable implementation', $exception->getPrevious()->getMessage());
        }
    }

    /** @throws Throwable When fixture setup or the operation under test throws */
    public function testRejectsAHandlerMappingToANonPropertyAttributeClass(): void
    {
        $container = ContainerFactory::create([
            ThirdPartyPropertyHandler::class => [
                new AttributeMetadata(new PropertyHandler(
                    Dependency::class,
                )),
            ],
        ]);

        try {
            new PropertyResolver($container)->resolve(new PropertyDefinition(PropertyInjectedService::class));
            $this->fail('Expected a non-Attribute property mapping to fail.');
        } catch (ResolutionException $exception) {
            $this->assertInstanceOf(InvalidArgumentException::class, $exception->getPrevious());
            $this->assertStringContainsString('native Attribute class', $exception->getPrevious()->getMessage());
        }
    }

    /** @throws Throwable When fixture setup or the operation under test throws */
    public function testExistingInterfaceTypeDoesNotFallBackToItsPropertyDefault(): void
    {
        $resolver = new PropertyResolver(ContainerFactory::create());

        $this->expectException(NotFoundExceptionInterface::class);

        $resolver->resolve(new PropertyDefinition(MissingProviderPropertyService::class));
    }

    /** @throws Throwable When fixture setup or the operation under test throws */
    public function testRejectsAPropertyWithoutIdentifierTypeOrDefault(): void
    {
        $this->expectException(ResolutionException::class);
        $this->expectExceptionMessageIsOrContains('Unable to resolve property');

        new PropertyResolver(ContainerFactory::create())->resolve(
            new PropertyDefinition(UnresolvablePropertyService::class),
        );
    }

    /** @throws Throwable When fixture setup or the operation under test throws */
    public function testRejectsStaticInjection(): void
    {
        $this->expectException(ResolutionException::class);
        $this->expectExceptionMessageIsOrContains('Static property');

        new PropertyResolver(ContainerFactory::create())->resolve(new PropertyDefinition(StaticInjectedService::class));
    }

    /** @throws Throwable When fixture setup or the operation under test throws
     *
     * Deliberately repeats Autowired so native attribute validation must reject it
     *
     * @noinspection PhpAttributeIsNotRepeatableInspection
     */
    public function testRejectsRepeatedAutowiredAttributesUsingTheNativeRepeatabilityRule(): void
    {
        $duplicate = new class() {
            #[Autowired]
            #[Autowired]
            public Dependency $dependency;
        };

        try {
            new PropertyResolver(ContainerFactory::create())->resolve(new PropertyDefinition($duplicate::class));
            $this->fail('Expected repeated Autowired metadata to fail.');
        } catch (ResolutionException $exception) {
            $this->assertInstanceOf(Error::class, $exception->getPrevious());
            $this->assertStringContainsString('must not be repeated', $exception->getPrevious()->getMessage());
        }
    }

    /** @throws Throwable When fixture setup or the operation under test throws */
    public function testRejectsAnInvalidAutowiredIdentifier(): void
    {
        $invalid = new class() {
            #[Autowired('SuperKernel\\Di\\Tests\\Fixture\\UndefinedAutowiredTarget')]
            public mixed $dependency;
        };

        try {
            new PropertyResolver(ContainerFactory::create())->resolve(new PropertyDefinition($invalid::class));
            $this->fail('Expected an invalid Autowired identifier to fail.');
        } catch (ResolutionException $exception) {
            $this->assertInstanceOf(InvalidArgumentException::class, $exception->getPrevious());
            $this->assertStringContainsString('does not exist', $exception->getPrevious()->getMessage());
        }
    }

    /** @throws Throwable When fixture setup or the operation under test throws */
    public function testAutowiredDefaultsToLazyConcretePropertyLookup(): void
    {
        LazyDependency::$constructions = 0;
        $container = ContainerFactory::create();
        $service = $container->get(LazyPropertyService::class);
        $dependency = $service->dependency();
        $reflection = new ReflectionClass(LazyDependency::class);

        $this->assertTrue($reflection->isUninitializedLazyObject($dependency));
        $this->assertSame(0, LazyDependency::$constructions);
        $this->assertSame('pong', $dependency->ping());
        $this->assertTrue($reflection->isUninitializedLazyObject($dependency));
        $this->assertSame(0, LazyDependency::$constructions);
        $this->assertSame(1, $dependency->state());
        $this->assertFalse($reflection->isUninitializedLazyObject($dependency));
        $this->assertSame(1, LazyDependency::$constructions);

        $canonical = $container->get(LazyDependency::class);

        $this->assertNotSame($dependency, $canonical);
        $this->assertSame(1, $canonical->state());
        $this->assertSame(1, LazyDependency::$constructions);
    }

    /** @throws Throwable When fixture setup or the operation under test throws */
    public function testLazyInterfacePropertyUsesItsProviderAsTheProxyClass(): void
    {
        LazyAnnotatedProvider::$constructions = 0;
        $container = ContainerFactory::create([
            LazyAnnotatedProvider::class => [
                new AttributeMetadata(new Provider(
                    ProviderSubcontract::class,
                )),
            ],
        ]);
        $service = $container->get(LazyProviderPropertyService::class);
        $provider = $service->provider();
        $reflection = new ReflectionClass(LazyAnnotatedProvider::class);

        $this->assertInstanceOf(LazyAnnotatedProvider::class, $provider);
        $this->assertTrue($reflection->isUninitializedLazyObject($provider));
        $this->assertSame(0, LazyAnnotatedProvider::$constructions);
        $this->assertSame(1, $provider->state());
        $this->assertSame(1, LazyAnnotatedProvider::$constructions);

        $canonical = $container->get(ProviderSubcontract::class);

        $this->assertNotSame($provider, $canonical);
        $this->assertSame($canonical, $container->get(LazyAnnotatedProvider::class));
    }

    /** @throws Throwable When fixture setup or the operation under test throws */
    public function testFactoryInterfaceWithoutAConcreteProxyClassUsesTheCanonicalEntry(): void
    {
        $consumer = new class() {
            #[Autowired(ProviderContract::class)]
            public ProviderContract $provider;
        };
        $factory = new class() {
            public function __invoke(): ProviderContract
            {
                return new AnnotatedProvider();
            }
        };
        $container = ContainerFactory::create([
            $factory::class => [
                new AttributeMetadata(new FactoryProvider(
                    ProviderContract::class,
                )),
            ],
        ]);

        $service = $container->get($consumer::class);

        $this->assertInstanceOf(AnnotatedProvider::class, $service->provider);
        $this->assertSame($container->get(ProviderContract::class), $service->provider);
    }

    /** @throws Throwable When the explicitly identified dependency cannot be resolved */
    public function testExplicitIdentifiersUseLazyProxiesByDefault(): void
    {
        LazyDependency::$constructions = 0;
        $consumer = new class() {
            #[Autowired(LazyDependency::class)]
            public object $dependency;
        };
        $container = ContainerFactory::create();
        $service = $container->get($consumer::class);
        $reflection = new ReflectionClass(LazyDependency::class);

        $this->assertTrue($reflection->isUninitializedLazyObject($service->dependency));
        $this->assertSame(0, LazyDependency::$constructions);
        $this->assertSame(1, $service->dependency->state());
        $this->assertSame(1, LazyDependency::$constructions);
        $canonical = $container->get(LazyDependency::class);
        $reflection->getProperty('state')->setValue($canonical, 2);

        $this->assertSame(2, $service->dependency->state());
        $this->assertNotSame($canonical, $service->dependency);
    }

    /** @throws Throwable When a service without stored properties cannot be resolved */
    public function testClassesWithoutStoredPropertiesKeepTheirConstructorAndCanonicalIdentity(): void
    {
        StatelessDependency::$constructions = 0;
        $consumer = new class() {
            #[Autowired]
            public StatelessDependency $dependency;
        };
        $container = ContainerFactory::create();
        $service = $container->get($consumer::class);

        $this->assertSame(1, $service->dependency->count);
        $this->assertSame($container->get(StatelessDependency::class), $service->dependency);
        $this->assertSame(1, StatelessDependency::$constructions);
    }

    /** @throws Throwable When retained internal-class entries cannot be injected */
    public function testInternalClassesAndTheirSubclassesUseTheCanonicalEntries(): void
    {
        $consumer = new class() {
            #[Autowired]
            public DateTimeImmutable $date;

            #[Autowired]
            public InternalDateTime $inherited;

            #[Autowired]
            public stdClass $data;
        };
        $date = new DateTimeImmutable('2026-01-01');
        $inherited = new InternalDateTime('2026-01-02');
        $data = (object) [
            'message' => 'retained',
        ];
        $container = new Container(ContainerFactory::collector(), [
            DateTimeImmutable::class                             => $date,
            SuperKernel\Di\Tests\Fixture\InternalDateTime::class => $inherited,
            stdClass::class                                      => $data,
        ]);
        $service = $container->get($consumer::class);

        $this->assertSame($date, $service->date);
        $this->assertSame($inherited, $service->inherited);
        $this->assertSame('inherited', $service->inherited->marker);
        $this->assertSame($data, $service->data);
        $this->assertSame('retained', $service->data->message);
    }

    /** @throws Throwable When fixture setup or the operation under test throws */
    public function testLazyInitializationFailureLeavesTheProxyRetryable(): void
    {
        LazyThrowingService::$constructions = 0;
        $container = ContainerFactory::create();
        $dependency = $container->get(LazyThrowingPropertyService::class)->dependency();
        $reflection = new ReflectionClass(LazyThrowingService::class);

        for ($attempt = 1; 2 >= $attempt; ++$attempt) {
            try {
                // Reading the property triggers lazy initialization; the return value is irrelevant.
                /* @noinspection PhpExpressionResultUnusedInspection */
                $dependency->state();
                $this->fail('Expected lazy dependency construction to fail.');
            } catch (ResolutionException $exception) {
                $this->assertInstanceOf(RuntimeException::class, $exception->getPrevious());
                $this->assertSame($attempt, LazyThrowingService::$constructions);
                $this->assertTrue($reflection->isUninitializedLazyObject($dependency));
            }
        }
    }

    /** @throws Throwable When fixture setup or the operation under test throws */
    public function testClassResolutionWrapsAnIncompatiblePropertyAssignment(): void
    {
        try {
            ContainerFactory::create()->get(MismatchedPropertyService::class);
            $this->fail('Expected incompatible property injection to fail.');
        } catch (ResolutionException $exception) {
            $this->assertInstanceOf(TypeError::class, $exception->getPrevious());
            $this->assertStringContainsString('Unable to inject property', $exception->getMessage());
        }
    }
}
