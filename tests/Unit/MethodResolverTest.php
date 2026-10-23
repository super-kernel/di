<?php

declare(strict_types=1);

namespace SuperKernel\Di\Tests\Unit;

use PHPUnit\Framework\TestCase;
use SuperKernel\Di\Container;
use SuperKernel\Di\Contract\ResolverFactoryInterface;
use SuperKernel\Di\Definition\ClassDefinition;
use SuperKernel\Di\Definition\MethodDefinition;
use SuperKernel\Di\Exception\ResolutionException;
use SuperKernel\Di\Resolver\MethodResolver;
use SuperKernel\Di\Tests\Fixture\ContainerFactory;
use SuperKernel\Di\Tests\Fixture\Dependency;
use SuperKernel\Di\Tests\Fixture\MethodArgumentService;
use SuperKernel\Di\Tests\Fixture\Provider\MethodInjectedProvider;
use SuperKernel\Di\Tests\Fixture\ReferenceMethodService;
use SuperKernel\Di\Tests\Fixture\Service;
use Throwable;

final class MethodResolverTest extends TestCase
{
    public function testDefinitionDefaultsNullAndOmittedMethodsToTheConstructor(): void
    {
        $omitted = new MethodDefinition(MethodInjectedProvider::class);
        $null = new MethodDefinition(MethodInjectedProvider::class, null);
        $invoke = new MethodDefinition(MethodInjectedProvider::class, '__invoke');

        $this->assertSame('__construct', $omitted->methodName);
        $this->assertSame('__construct', $null->methodName);
        $this->assertSame($omitted->identifier(), $null->identifier());
        $this->assertNotSame(MethodInjectedProvider::class, $omitted->identifier());
        $this->assertNotSame($omitted->identifier(), $invoke->identifier());
    }

    /** @throws Throwable When fixture setup or the operation under test throws */
    public function testResolvesConstructorAndInvokeArgumentsInDeclarationOrder(): void
    {
        $container = $this->createContainer();
        $resolverFactory = $container->get(ResolverFactoryInterface::class);
        $this->assertInstanceOf(ResolverFactoryInterface::class, $resolverFactory);
        $resolver = new MethodResolver($container);
        $constructorDefinition = new MethodDefinition(MethodInjectedProvider::class);
        $invokeDefinition = new MethodDefinition(MethodInjectedProvider::class, '__invoke');

        $this->assertTrue($resolver->support($constructorDefinition));
        $this->assertFalse($resolver->support(new ClassDefinition(MethodInjectedProvider::class)));
        $this->assertTrue($resolverFactory->support($constructorDefinition));
        $this->assertSame(
            [
                $container->get(Dependency::class),
            ],
            $resolverFactory->resolve($constructorDefinition),
        );
        $this->assertSame(
            [
                $container->get(Service::class),
            ],
            $resolverFactory->resolve($invokeDefinition),
        );
        $this->assertSame([], $resolverFactory->resolve(new MethodDefinition(Dependency::class)));
    }

    /** @throws Throwable When fixture setup or the operation under test throws */
    public function testRejectsAnUnknownMethod(): void
    {
        $resolverFactory = $this->createContainer()->get(ResolverFactoryInterface::class);

        $this->assertInstanceOf(ResolverFactoryInterface::class, $resolverFactory);
        $this->expectException(ResolutionException::class);
        $this->expectExceptionMessageIsOrContains('Method ' . Dependency::class . '::missing does not exist');

        $resolverFactory->resolve(new MethodDefinition(Dependency::class, 'missing'));
    }

    /** @throws Throwable When fixture setup or the operation under test throws */
    public function testUsesDefaultsAndNullableFallbacksAndOmitsVariadics(): void
    {
        $container = $this->createContainer();
        $resolverFactory = $container->get(ResolverFactoryInterface::class);

        $this->assertInstanceOf(ResolverFactoryInterface::class, $resolverFactory);
        $this->assertSame(
            [
                $container->get(Dependency::class),
                'default',
                null,
            ],
            $resolverFactory->resolve(new MethodDefinition(MethodArgumentService::class, 'execute')),
        );
    }

    /** @throws Throwable When fixture setup or the operation under test throws */
    public function testCustomParametersTakePrecedenceByNameThenPosition(): void
    {
        $container = $this->createContainer();
        $resolver = new MethodResolver($container);

        $arguments = $resolver->resolve(new MethodDefinition(
            MethodArgumentService::class,
            'execute',
            [
                0      => 'position dependency',
                1      => 'position name',
                'name' => 'named name',
            ],
        ));

        $this->assertSame([
            'position dependency',
            'named name',
            null,
        ], $arguments);
    }

    /** @throws Throwable When fixture setup or the operation under test throws */
    public function testRejectsReferenceParameters(): void
    {
        $resolverFactory = $this->createContainer()->get(ResolverFactoryInterface::class);

        $this->assertInstanceOf(ResolverFactoryInterface::class, $resolverFactory);
        $this->expectException(ResolutionException::class);
        $this->expectExceptionMessageIsOrContains('references are unsupported');

        $resolverFactory->resolve(new MethodDefinition(ReferenceMethodService::class, 'consume'));
    }

    private function createContainer(): Container
    {
        return ContainerFactory::create();
    }
}
