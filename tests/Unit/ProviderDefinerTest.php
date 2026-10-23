<?php

declare(strict_types=1);

namespace SuperKernel\Di\Tests\Unit;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use SuperKernel\Annotator\AnnotationCollector;
use SuperKernel\Annotator\Model\AttributeMetadata;
use SuperKernel\Annotator\Model\PackageAnnotations;
use SuperKernel\Attribute\Provider;
use SuperKernel\Di\Definer\ProviderDefiner;
use SuperKernel\Di\Definition\ClassDefinition;
use SuperKernel\Di\Definition\ProviderDefinition;
use SuperKernel\Di\Exception\NotFoundException;
use SuperKernel\Di\Resolver\ClassResolver;
use SuperKernel\Di\Tests\Fixture\AnnotatedProvider;
use SuperKernel\Di\Tests\Fixture\ContainerFactory;
use SuperKernel\Di\Tests\Fixture\Dependency;
use SuperKernel\Di\Tests\Fixture\FactoryProduct;
use SuperKernel\Di\Tests\Fixture\Provider\NonPublicConstructorProvider;
use SuperKernel\Di\Tests\Fixture\ProviderContract;
use SuperKernel\Di\Tests\Fixture\ProviderSubcontract;
use SuperKernel\Di\Tests\Fixture\Service;
use Throwable;

final class ProviderDefinerTest extends TestCase
{
    public function testMapsTheAnnotatedProviderAcrossATransitiveInterfaceRelationship(): void
    {
        $definer = new ProviderDefiner(self::collector([
            AnnotatedProvider::class => [
                new AttributeMetadata(new Provider(
                    ProviderContract::class,
                )),
            ],
        ]));

        $this->assertTrue($definer->support(ProviderContract::class));
        $this->assertFalse($definer->support(ProviderSubcontract::class));
        $this->assertFalse($definer->support(AnnotatedProvider::class));

        $definition = $definer->get(ProviderContract::class);

        $this->assertInstanceOf(ProviderDefinition::class, $definition);
        $this->assertSame(ProviderContract::class, $definition->className);
        $this->assertSame(ProviderContract::class, $definition->identifier());
        $this->assertSame(AnnotatedProvider::class, $definition->providerClass);
        $container = ContainerFactory::create();
        $this->assertFalse(new ClassResolver($container)->support($definition));
    }

    public function testAcceptsTheNamedClassNameArgument(): void
    {
        $definer = new ProviderDefiner(self::collector([
            AnnotatedProvider::class => [
                new AttributeMetadata(new Provider(
                    className : ProviderContract::class,
                )),
            ],
        ]));

        $this->assertTrue($definer->support(ProviderContract::class));
    }

    public function testMapsAnInterfaceProviderToTheNextProviderInTheChain(): void
    {
        $definer = new ProviderDefiner(self::collector([
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
        ]));

        $root = $definer->get(ProviderContract::class);
        $next = $definer->get(ProviderSubcontract::class);

        self::assertInstanceOf(ProviderDefinition::class, $root);
        self::assertInstanceOf(ProviderDefinition::class, $next);
        self::assertSame(ProviderSubcontract::class, $root->providerClass);
        self::assertSame(AnnotatedProvider::class, $next->providerClass);
    }

    /** @throws Throwable When the self-provided service or its dependency cannot be resolved */
    public function testAutowiresAndRetainsASelfProvidedClass(): void
    {
        $classes = [
            Service::class => [
                new AttributeMetadata(new Provider(Service::class)),
            ],
        ];
        $definer = new ProviderDefiner(self::collector($classes));
        $definition = $definer->get(Service::class);

        self::assertInstanceOf(ClassDefinition::class, $definition);
        self::assertSame(Service::class, $definition->identifier());

        $container = ContainerFactory::create($classes);
        $service = $container->get(Service::class);

        self::assertInstanceOf(Service::class, $service);
        self::assertSame($service, $container->get(Service::class));
        self::assertSame($service->dependency, $container->get(Dependency::class));
    }

    public function testRejectsASelfProvidedInterface(): void
    {
        $definer = new ProviderDefiner(self::collector([
            ProviderContract::class => [
                new AttributeMetadata(new Provider(ProviderContract::class)),
            ],
        ]));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIsOrContains('must be instantiable');

        $definer->get(ProviderContract::class);
    }

    public function testRejectsASelfProvidedClassWithANonPublicConstructor(): void
    {
        $definer = new ProviderDefiner(self::collector([
            NonPublicConstructorProvider::class => [
                new AttributeMetadata(new Provider(NonPublicConstructorProvider::class)),
            ],
        ]));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIsOrContains('must be instantiable');

        $definer->get(NonPublicConstructorProvider::class);
    }

    public function testRejectsDuplicateProviderMappings(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIsOrContains('is declared by both');

        new ProviderDefiner(self::collector([
            AnnotatedProvider::class => [
                new AttributeMetadata(new Provider(
                    ProviderContract::class,
                )),
            ],
            FactoryProduct::class => [
                new AttributeMetadata(new Provider(
                    ProviderContract::class,
                )),
            ],
        ]));
    }

    public function testRejectsAClassThatDoesNotProvideTheMappedIdentifier(): void
    {
        $definer = new ProviderDefiner(self::collector([
            FactoryProduct::class => [
                new AttributeMetadata(new Provider(
                    ProviderContract::class,
                )),
            ],
        ]));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIsOrContains('must be a distinct provider');

        $definer->get(ProviderContract::class);
    }

    public function testUnknownIdentifierRaisesNotFound(): void
    {
        $definer = new ProviderDefiner(self::collector([]));

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
