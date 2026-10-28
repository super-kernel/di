<?php

declare(strict_types=1);

namespace SuperKernel\Di\Tests\Unit;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface as PsrContainerInterface;
use SuperKernel\Annotator\AnnotationCollector;
use SuperKernel\Annotator\Model\AttributeMetadata;
use SuperKernel\Annotator\Model\PackageAnnotations;
use SuperKernel\Attribute\FactoryProvider;
use SuperKernel\Contract\ContainerInterface;
use SuperKernel\Di\Definer\FactoryDefiner;
use SuperKernel\Di\Definition\FactoryDefinition;
use SuperKernel\Di\Exception\NotFoundException;
use SuperKernel\Di\Provider\ContainerProvider;
use SuperKernel\Di\Resolver\ClassResolver;
use SuperKernel\Di\Tests\Fixture\ContainerFactory;
use SuperKernel\Di\Tests\Fixture\FactoryProduct;
use SuperKernel\Di\Tests\Fixture\Provider\AnnotatedProvider;
use SuperKernel\Di\Tests\Fixture\Provider\NonPublicConstructorProvider;

final class FactoryDefinerTest extends TestCase
{
    public function testMapsTheAnnotatedProviderWithoutInvokingIt(): void
    {
        $definer = new FactoryDefiner(self::collector([
            AnnotatedProvider::class => [
                new AttributeMetadata(new FactoryProvider(
                    className : FactoryProduct::class,
                )),
            ],
        ]));

        $this->assertTrue($definer->support(FactoryProduct::class));
        $this->assertFalse($definer->support('unknown'));

        $definition = $definer->get(FactoryProduct::class);

        $this->assertInstanceOf(FactoryDefinition::class, $definition);
        $this->assertSame(FactoryProduct::class, $definition->className);
        $this->assertSame(FactoryProduct::class, $definition->identifier());
        $this->assertSame(AnnotatedProvider::class, $definition->factoryClass);
        $container = ContainerFactory::create();
        $this->assertFalse(new ClassResolver($container)->support($definition));
    }

    public function testRejectsDuplicateFactoryProviderMappings(): void
    {
        $duplicate = new class() {
            public function __construct()
            {
            }

            /**
             * The mixed declaration exercises factory resolution independently of the returned value.
             *
             * @noinspection PhpMixedReturnTypeCanBeReducedInspection
             */
            public function __invoke(): mixed
            {
                return null;
            }
        };

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIsOrContains('is declared by both');

        new FactoryDefiner(self::collector([
            AnnotatedProvider::class => [
                new AttributeMetadata(new FactoryProvider(
                    FactoryProduct::class,
                )),
            ],
            $duplicate::class => [
                new AttributeMetadata(new FactoryProvider(
                    FactoryProduct::class,
                )),
            ],
        ]));
    }

    public function testMapsOneRepeatableFactoryProviderToBothContainerInterfaces(): void
    {
        $definer = new FactoryDefiner(self::collector([
            ContainerProvider::class => [
                new AttributeMetadata(new FactoryProvider(
                    ContainerInterface::class,
                )),
                new AttributeMetadata(new FactoryProvider(
                    PsrContainerInterface::class,
                )),
            ],
        ]));

        $frameworkDefinition = $definer->get(ContainerInterface::class);
        $psrDefinition = $definer->get(PsrContainerInterface::class);

        $this->assertInstanceOf(FactoryDefinition::class, $frameworkDefinition);
        $this->assertInstanceOf(FactoryDefinition::class, $psrDefinition);
        $this->assertSame(ContainerInterface::class, $frameworkDefinition->identifier());
        $this->assertSame(PsrContainerInterface::class, $psrDefinition->identifier());
        $this->assertSame(ContainerProvider::class, $frameworkDefinition->factoryClass);
        $this->assertSame(ContainerProvider::class, $psrDefinition->factoryClass);
    }

    public function testAcceptsAFactoryProviderWithAnImplicitPublicConstructor(): void
    {
        $factory = new class() {
            /**
             * The mixed declaration exercises factory resolution independently of the returned value.
             *
             * @noinspection PhpMixedReturnTypeCanBeReducedInspection
             */
            public function __invoke(): mixed
            {
                return null;
            }
        };
        $definer = new FactoryDefiner(self::collector([
            $factory::class => [
                new AttributeMetadata(new FactoryProvider(
                    FactoryProduct::class,
                )),
            ],
        ]));

        $definition = $definer->get(FactoryProduct::class);

        $this->assertInstanceOf(FactoryDefinition::class, $definition);
        $this->assertSame($factory::class, $definition->factoryClass);
    }

    public function testRejectsAFactoryProviderWithANonPublicConstructor(): void
    {
        $definer = new FactoryDefiner(self::collector([
            NonPublicConstructorProvider::class => [
                new AttributeMetadata(new FactoryProvider(
                    FactoryProduct::class,
                )),
            ],
        ]));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIsOrContains('implicit or public constructor');

        $definer->get(FactoryProduct::class);
    }

    public function testRejectsAFactoryProviderWithoutInvoke(): void
    {
        $factory = new class() {
            public function __construct()
            {
            }
        };
        $definer = new FactoryDefiner(self::collector([
            $factory::class => [
                new AttributeMetadata(new FactoryProvider(
                    FactoryProduct::class,
                )),
            ],
        ]));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIsOrContains('must declare a public non-static __invoke');

        $definer->get(FactoryProduct::class);
    }

    public function testAcceptsRequiredInvokeParametersForRuntimeResolution(): void
    {
        $factory = new class() {
            public function __construct()
            {
            }

            /**
             * The mixed declaration exercises factory resolution independently of the returned value.
             *
             * @noinspection PhpMixedReturnTypeCanBeReducedInspection
             */
            public function __invoke(string $value): mixed
            {
                return $value;
            }
        };
        $definer = new FactoryDefiner(self::collector([
            $factory::class => [
                new AttributeMetadata(new FactoryProvider(
                    FactoryProduct::class,
                )),
            ],
        ]));

        $definition = $definer->get(FactoryProduct::class);

        $this->assertInstanceOf(FactoryDefinition::class, $definition);
        $this->assertSame($factory::class, $definition->factoryClass);
    }

    public function testUnknownIdentifierRaisesNotFound(): void
    {
        $definer = new FactoryDefiner(self::collector([]));

        $this->expectException(NotFoundException::class);

        $definer->get('unknown');
    }

    /**
     * @param array<class-string, list<AttributeMetadata<object>>> $classes
     */
    private static function collector(array $classes): AnnotationCollector
    {
        return new AnnotationCollector([
            new PackageAnnotations('acme/package', null, $classes, [], []),
        ]);
    }
}
