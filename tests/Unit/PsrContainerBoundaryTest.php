<?php

declare(strict_types=1);

namespace SuperKernel\Di\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use stdClass;
use SuperKernel\Contract\AnnotationCollectorInterface;
use SuperKernel\Di\Contract\DefinitionInterface;
use SuperKernel\Di\Exception\ResolutionException;
use SuperKernel\Di\Factory\DefinitionFactory;
use SuperKernel\Di\Factory\ResolverFactory;
use SuperKernel\Di\Property\PropertyDispatcher;
use TypeError;

final class PsrContainerBoundaryTest extends TestCase
{
    /** @throws ContainerExceptionInterface When a container dependency cannot be resolved */
    public function testDefinitionLookupPreservesTheTypedCollectorFailureAsItsCause(): void
    {
        $factory = new DefinitionFactory($this->wrongCollector());

        try {
            $factory->get(stdClass::class);
            self::fail('An incompatible collector must fail at the consumer boundary.');
        } catch (ResolutionException $exception) {
            self::assertInstanceOf(TypeError::class, $exception->getPrevious());
            self::assertStringContainsString('DefinitionFactory::collector()', $exception->getPrevious()->getMessage());
            self::assertStringContainsString(
                AnnotationCollectorInterface::class,
                $exception->getPrevious()->getMessage(),
            );
        }
    }

    public function testResolverDiscoveryRejectsAnIncompatibleCollector(): void
    {
        $factory = new ResolverFactory($this->wrongCollector());
        $definition = $this->createStub(DefinitionInterface::class);

        $this->expectException(TypeError::class);
        $this->expectExceptionMessageIsOrContains('ResolverFactory::collector()');

        $factory->support($definition);
    }

    /** @throws ContainerExceptionInterface When a container dependency cannot be resolved */
    public function testPropertyDiscoveryRejectsAnIncompatibleCollector(): void
    {
        $dispatcher = new PropertyDispatcher($this->wrongCollector());

        $this->expectException(TypeError::class);
        $this->expectExceptionMessageIsOrContains('PropertyDispatcher::collector()');

        self::assertFalse($dispatcher->supports(stdClass::class));
    }

    private function wrongCollector(): ContainerInterface
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->expects(self::once())->method('get')->with(AnnotationCollectorInterface::class)
            ->willReturn(new stdClass());

        return $container;
    }
}
