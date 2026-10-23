<?php

declare(strict_types=1);

namespace SuperKernel\Di\Tests\Fixture;

use SuperKernel\Annotator\AnnotationCollector;
use SuperKernel\Annotator\Model\AttributeMetadata;
use SuperKernel\Annotator\Model\PackageAnnotations;
use SuperKernel\Attribute\Autowired;
use SuperKernel\Attribute\Definer;
use SuperKernel\Attribute\FactoryProvider;
use SuperKernel\Attribute\PropertyHandler;
use SuperKernel\Attribute\Resolver;
use SuperKernel\Contract\ReflectorInterface;
use SuperKernel\Di\Container;
use SuperKernel\Di\Definer\ClassDefiner;
use SuperKernel\Di\Definer\FactoryDefiner;
use SuperKernel\Di\Definer\ProviderDefiner;
use SuperKernel\Di\PropertyHandler\AutowiredPropertyHandler;
use SuperKernel\Di\Resolver\ClassResolver;
use SuperKernel\Di\Resolver\FactoryResolver;
use SuperKernel\Di\Resolver\MethodResolver;
use SuperKernel\Di\Resolver\PropertyResolver;
use SuperKernel\Di\Resolver\ProviderResolver;
use SuperKernel\Reflector\Provider\ReflectorProvider;

use function array_replace;

final class ContainerFactory
{
    /**
     * @param array<class-string, list<AttributeMetadata<object>>> $classes
     */
    public static function create(array $classes = []): Container
    {
        return new Container(self::collector($classes));
    }

    /**
     * @param array<class-string, list<AttributeMetadata<object>>> $classes
     */
    public static function collector(array $classes = []): AnnotationCollector
    {
        return new AnnotationCollector([
            new PackageAnnotations(
                'super-kernel/di',
                null,
                array_replace([
                    ProviderDefiner::class => [
                        new AttributeMetadata(new Definer(
                            priority : 200,
                        )),
                    ],
                    FactoryDefiner::class => [
                        new AttributeMetadata(new Definer(
                            priority : 100,
                        )),
                    ],
                    ClassDefiner::class => [
                        new AttributeMetadata(new Definer()),
                    ],
                    FactoryResolver::class => [
                        new AttributeMetadata(new Resolver(
                            priority : 400,
                        )),
                    ],
                    ProviderResolver::class => [
                        new AttributeMetadata(new Resolver(
                            priority : 300,
                        )),
                    ],
                    ClassResolver::class => [
                        new AttributeMetadata(new Resolver(
                            priority : 200,
                        )),
                    ],
                    PropertyResolver::class => [
                        new AttributeMetadata(new Resolver(
                            priority : 100,
                        )),
                    ],
                    MethodResolver::class => [
                        new AttributeMetadata(new Resolver(
                            priority : 0,
                        )),
                    ],
                    AutowiredPropertyHandler::class => [
                        new AttributeMetadata(new PropertyHandler(
                            Autowired::class,
                        )),
                    ],
                    ReflectorProvider::class => [
                        new AttributeMetadata(new FactoryProvider(
                            ReflectorInterface::class,
                        )),
                    ],
                ], $classes),
                [],
                [],
            ),
        ]);
    }
}
