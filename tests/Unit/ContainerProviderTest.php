<?php

declare(strict_types=1);

namespace SuperKernel\Di\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface as PsrContainerInterface;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionProperty;
use SuperKernel\Annotator\Model\AttributeMetadata;
use SuperKernel\Attribute\FactoryProvider;
use SuperKernel\Contract\AnnotationCollectorInterface;
use SuperKernel\Contract\BasePathInterface;
use SuperKernel\Contract\ContainerInterface;
use SuperKernel\Contract\PackageManagerInterface;
use SuperKernel\Contract\ReflectorInterface;
use SuperKernel\Contract\VendorDirInterface;
use SuperKernel\Di\Container;
use SuperKernel\Di\Contract\DefinitionFactoryInterface;
use SuperKernel\Di\Contract\ResolverFactoryInterface;
use SuperKernel\Di\Factory\DefinitionFactory;
use SuperKernel\Di\Factory\ResolverFactory;
use SuperKernel\Di\Provider\ContainerProvider;
use SuperKernel\Di\Tests\Fixture\ContainerFactory;
use SuperKernel\Reflector\Facade\ReflectorFacade;
use SuperKernel\Reflector\Provider\ReflectorProvider;
use SuperKernel\Reflector\Reflector;
use Throwable;

use function array_map;

final class ContainerProviderTest extends TestCase
{
    /** @throws Throwable When fixture setup or the operation under test throws */
    public function testContainerProviderDeclaresBothFactoryProviderAttributes(): void
    {
        $reflection = new ReflectionClass(ContainerProvider::class);
        $attributes = $reflection->getAttributes(FactoryProvider::class);
        $constructor = $reflection->getConstructor();
        $invoke = $reflection->getMethod('__invoke');

        $this->assertTrue($reflection->isFinal());
        $this->assertTrue($reflection->isReadOnly());
        $this->assertSame(
            [
                ContainerInterface::class,
                PsrContainerInterface::class,
            ],
            array_map(
                static fn(ReflectionAttribute $attribute): string => $attribute->newInstance()->className,
                $attributes,
            ),
        );
        $this->assertNull($constructor);
        $this->assertSame(1, $invoke->getNumberOfParameters());
        $parameter = $invoke->getParameters()[0];
        $type = $parameter->getType();
        $this->assertInstanceOf(ReflectionNamedType::class, $type);
        $this->assertSame(PsrContainerInterface::class, $type->getName());
        $this->assertTrue($parameter->allowsNull());
        $this->assertTrue($parameter->isDefaultValueAvailable());
        $this->assertNull($parameter->getDefaultValue());
        $this->assertSame(PsrContainerInterface::class, (string) $invoke->getReturnType());
        $this->assertSame(0, $invoke->getNumberOfRequiredParameters());
        $this->assertFalse($reflection->hasProperty('instance'));
    }

    /** @throws Throwable When fixture setup or the operation under test throws */
    public function testContainerProviderReturnsTheInjectedRoot(): void
    {
        $container = ContainerFactory::create();
        $provider = new ContainerProvider();

        $this->assertSame($container, $provider($container));
        $this->assertSame($container, $provider($container));
    }

    /** @throws Throwable When fixture setup or the operation under test throws */
    public function testContainerProviderCreatesTheFirstRoot(): void
    {
        $container = new ContainerProvider()();

        $this->assertInstanceOf(Container::class, $container);
        $this->assertSame($container, $container->get(ContainerInterface::class));
        $this->assertSame($container, $container->get(PsrContainerInterface::class));
        $this->assertInstanceOf(
            AnnotationCollectorInterface::class,
            $container->get(AnnotationCollectorInterface::class),
        );
        $this->assertInstanceOf(BasePathInterface::class, $container->get(BasePathInterface::class));
        $reflector = $container->get(ReflectorInterface::class);
        $this->assertInstanceOf(Reflector::class, $reflector);
        $this->assertSame($reflector, $container->get(Reflector::class));
        $this->assertSame($reflector, new ReflectionProperty(ReflectorFacade::class, 'reflector')->getValue());
        $this->assertInstanceOf(ReflectorProvider::class, $container->get(ReflectorProvider::class));
        $vendorDir = $container->get(VendorDirInterface::class);
        $this->assertInstanceOf(VendorDirInterface::class, $vendorDir);
        $this->assertSame($vendorDir, $container->get(VendorDirInterface::class));
        $this->assertDirectoryExists($vendorDir . '/.cache/classes');
        $this->assertDirectoryExists($vendorDir . '/.cache/annotations');
        $this->assertFileExists($vendorDir . '/.cache/packages.ser');
        $this->assertInstanceOf(
            PackageManagerInterface::class,
            $container->get(PackageManagerInterface::class),
        );
    }

    /** @throws Throwable When fixture setup or the operation under test throws */
    public function testResolvesTheReflectorThroughItsFactoryMetadata(): void
    {
        $attribute = new ReflectionClass(ReflectorProvider::class)->getAttributes(FactoryProvider::class)[0];
        $container = ContainerFactory::create([
            ReflectorProvider::class => [
                new AttributeMetadata($attribute->newInstance()),
            ],
        ]);
        $reflector = $container->get(ReflectorInterface::class);

        $this->assertInstanceOf(Reflector::class, $reflector);
        $this->assertSame($reflector, $container->get(ReflectorInterface::class));
        $this->assertSame($reflector, new ReflectionProperty(ReflectorFacade::class, 'reflector')->getValue());
        $this->assertSame(
            $reflector->reflectClass(self::class),
            ReflectorFacade::reflectClass(self::class),
        );
        $this->assertInstanceOf(ReflectorProvider::class, $container->get(ReflectorProvider::class));
    }

    /** @throws Throwable When fixture setup or the operation under test throws */
    public function testAutowiredContainerProviderReturnsTheSuppliedRoot(): void
    {
        $container = ContainerFactory::create();
        $provider = $container->get(ContainerProvider::class);

        $this->assertInstanceOf(ContainerProvider::class, $provider);
        $this->assertSame($container, $provider($container));
    }

    /** @throws Throwable When fixture setup or the operation under test throws */
    public function testContainerOwnsTheCollectorAndCanonicalFactories(): void
    {
        $collector = ContainerFactory::collector();
        $container = new Container($collector);

        $this->assertSame($collector, $container->get(AnnotationCollectorInterface::class));
        $this->assertSame(
            $container->get(DefinitionFactoryInterface::class),
            $container->get(DefinitionFactory::class),
        );
        $this->assertSame(
            $container->get(ResolverFactoryInterface::class),
            $container->get(ResolverFactory::class),
        );
    }
}
