<?php

declare(strict_types=1);

namespace SuperKernel\Di\Tests\Unit;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use SuperKernel\Attribute\Autowired;
use SuperKernel\Attribute\Definer;
use SuperKernel\Attribute\PropertyHandler;
use SuperKernel\Attribute\Resolver;
use SuperKernel\Di\Definer\ClassDefiner;
use SuperKernel\Di\Definer\FactoryDefiner;
use SuperKernel\Di\Definer\ProviderDefiner;
use SuperKernel\Di\PropertyHandler\AutowiredPropertyHandler;
use SuperKernel\Di\Resolver\ClassResolver;
use SuperKernel\Di\Resolver\FactoryResolver;
use SuperKernel\Di\Resolver\MethodResolver;
use SuperKernel\Di\Resolver\PropertyResolver;
use SuperKernel\Di\Resolver\ProviderResolver;

final class AnnotationTest extends TestCase
{
    public function testDeclaresBuiltInDefinerPriorityOrder(): void
    {
        $provider = new ReflectionClass(ProviderDefiner::class)
            ->getAttributes(Definer::class)[0]
            ->newInstance()
            ->priority;
        $factory = new ReflectionClass(FactoryDefiner::class)
            ->getAttributes(Definer::class)[0]
            ->newInstance()
            ->priority;
        $class = new ReflectionClass(ClassDefiner::class)
            ->getAttributes(Definer::class)[0]
            ->newInstance()
            ->priority;

        $this->assertGreaterThan($factory, $provider);
        $this->assertGreaterThan($class, $factory);
    }

    public function testDeclaresBuiltInResolverPriorityOrder(): void
    {
        $factory = new ReflectionClass(FactoryResolver::class)
            ->getAttributes(Resolver::class)[0]
            ->newInstance()
            ->priority;
        $provider = new ReflectionClass(ProviderResolver::class)
            ->getAttributes(Resolver::class)[0]
            ->newInstance()
            ->priority;
        $class = new ReflectionClass(ClassResolver::class)
            ->getAttributes(Resolver::class)[0]
            ->newInstance()
            ->priority;
        $property = new ReflectionClass(PropertyResolver::class)
            ->getAttributes(Resolver::class)[0]
            ->newInstance()
            ->priority;
        $method = new ReflectionClass(MethodResolver::class)
            ->getAttributes(Resolver::class)[0]
            ->newInstance()
            ->priority;

        $this->assertGreaterThan($provider, $factory);
        $this->assertGreaterThan($class, $provider);
        $this->assertGreaterThan($property, $class);
        $this->assertGreaterThan($method, $property);
    }

    public function testDeclaresTheBuiltInAutowiredPropertyHandlerMapping(): void
    {
        $attributes = new ReflectionClass(AutowiredPropertyHandler::class)->getAttributes(PropertyHandler::class);

        $this->assertCount(1, $attributes);
        $this->assertSame(Autowired::class, $attributes[0]->newInstance()->attribute);
    }
}
