<?php

declare(strict_types=1);

namespace SuperKernel\Di\Tests\Unit;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionMethod;
use RuntimeException;
use SplPriorityQueue;
use SuperKernel\Annotator\Model\AttributeMetadata;
use SuperKernel\Attribute\Definer;
use SuperKernel\Contract\AnnotationCollectorInterface;
use SuperKernel\Contract\AttributeMetadataInterface;
use SuperKernel\Di\Contract\DefinitionFactoryInterface;
use SuperKernel\Di\Contract\DefinitionInterface;
use SuperKernel\Di\Definition\ClassDefinition;
use SuperKernel\Di\Exception\ResolutionException;
use SuperKernel\Di\Factory\DefinitionFactory;
use SuperKernel\Di\Tests\Fixture\ContainerFactory;
use SuperKernel\Di\Tests\Fixture\Dependency;
use SuperKernel\Di\Tests\Fixture\HighPriorityDefiner;
use SuperKernel\Di\Tests\Fixture\LowPriorityDefiner;
use SuperKernel\Di\Tests\Fixture\SkippingDefiner;
use SuperKernel\Di\Tests\Fixture\ThrowingDefiner;
use Throwable;

final class DefinitionFactoryTest extends TestCase
{
    /** @throws Throwable When a definer or its metadata cannot be resolved */
    public function testOrdersDefinersUsingIndependentMetadata(): void
    {
        self::resetDefiners();
        $metadata = $this->createStub(AttributeMetadataInterface::class);
        $metadata->method('attribute')->willReturn(new Definer(priority: 20));
        $collector = $this->createMock(AnnotationCollectorInterface::class);
        $collector->expects($this->once())->method('getClassesWithAttribute')->with(Definer::class)->willReturn([
            LowPriorityDefiner::class => [
                new AttributeMetadata(new Definer(priority: 10)),
            ],
            HighPriorityDefiner::class => [
                $metadata,
            ],
        ]);
        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->once())->method('get')
            ->with(AnnotationCollectorInterface::class)->willReturn($collector);
        $factory = new DefinitionFactory($container);

        $definition = $factory->get('service');

        self::assertNotNull($definition);
        self::assertSame('high', $definition->identifier());
        self::assertSame($definition, $factory->get('service'));
        self::assertSame(1, HighPriorityDefiner::$getCalls);
        self::assertSame(0, LowPriorityDefiner::$supportCalls);
    }

    public function testContractReturnsDefinitionsOrNull(): void
    {
        $method = new ReflectionMethod(DefinitionFactoryInterface::class, 'get');

        $this->assertSame('?' . DefinitionInterface::class, (string) $method->getReturnType());
        $this->assertTrue($method->getReturnType()?->allowsNull());
    }

    /** @throws Throwable When fixture setup or the operation under test throws */
    public function testFactoryIsContainerBoundAndInitializesDefinersLazily(): void
    {
        $container = ContainerFactory::create();
        $factory = $container->get(DefinitionFactoryInterface::class);
        $reflection = new ReflectionClass(DefinitionFactory::class);
        $boundContainer = $reflection->getProperty('container');
        $definers = $reflection->getProperty('definers');

        $this->assertInstanceOf(DefinitionFactory::class, $factory);
        $this->assertSame($container, $boundContainer->getValue($factory));
        $this->assertFalse($definers->isInitialized($factory));
        $this->assertInstanceOf(ClassDefinition::class, $factory->get(Dependency::class));
        $this->assertInstanceOf(SplPriorityQueue::class, $definers->getRawValue($factory));
    }

    /** @throws Throwable When fixture setup or the operation under test throws */
    public function testUsesTheHighestPrioritySupportingDefinerAndCachesItsDefinition(): void
    {
        self::resetDefiners();
        $factory = self::factory([
            LowPriorityDefiner::class => [
                new AttributeMetadata(new Definer(
                    priority : 10,
                )),
            ],
            HighPriorityDefiner::class => [
                new AttributeMetadata(new Definer(
                    priority : 20,
                )),
            ],
        ]);

        $first = $factory->get('service');
        $second = $factory->get('service');

        $this->assertNotNull($first);
        $this->assertSame('high', $first->identifier());
        $this->assertSame($first, $second);
        $this->assertSame(1, HighPriorityDefiner::$supportCalls);
        $this->assertSame(1, HighPriorityDefiner::$getCalls);
        $this->assertSame(0, LowPriorityDefiner::$supportCalls);
        $this->assertSame(0, LowPriorityDefiner::$getCalls);
    }

    /** @throws Throwable When fixture setup or the operation under test throws */
    public function testPreservesRegistrationOrderWhenPrioritiesAreEqual(): void
    {
        self::resetDefiners();
        $factory = self::factory([
            HighPriorityDefiner::class => [
                new AttributeMetadata(new Definer(
                    priority : 10,
                )),
            ],
            LowPriorityDefiner::class => [
                new AttributeMetadata(new Definer(
                    priority : 10,
                )),
            ],
        ]);

        $definition = $factory->get('service');

        $this->assertNotNull($definition);
        $this->assertSame('high', $definition->identifier());
        $this->assertSame(1, HighPriorityDefiner::$supportCalls);
        $this->assertSame(0, LowPriorityDefiner::$supportCalls);
    }

    /** @throws Throwable When fixture setup or the operation under test throws */
    public function testContinuesToLowerPrioritiesUntilADefinerSupportsTheIdentifier(): void
    {
        SkippingDefiner::$supportCalls = 0;
        $factory = self::factory([
            SkippingDefiner::class => [
                new AttributeMetadata(new Definer(
                    priority : 300,
                )),
            ],
        ]);

        $definition = $factory->get(Dependency::class);

        $this->assertInstanceOf(ClassDefinition::class, $definition);
        $this->assertSame(1, SkippingDefiner::$supportCalls);
    }

    /** @throws Throwable When fixture setup or the operation under test throws */
    public function testCachesUnsupportedIdentifiers(): void
    {
        self::resetDefiners();
        $factory = self::factory([
            HighPriorityDefiner::class => [
                new AttributeMetadata(new Definer(
                    priority : 300,
                )),
            ],
        ]);

        $this->assertNull($factory->get('missing'));
        $this->assertNull($factory->get('missing'));
        $this->assertSame(1, HighPriorityDefiner::$supportCalls);
        $this->assertSame(0, HighPriorityDefiner::$getCalls);
    }

    /** @throws Throwable When fixture setup or the operation under test throws */
    public function testWrapsUnexpectedDefinerFailures(): void
    {
        $factory = self::factory([
            ThrowingDefiner::class => [
                new AttributeMetadata(new Definer(
                    priority : 300,
                )),
            ],
        ]);

        try {
            $factory->get('broken');
            $this->fail('Expected a definition lookup exception.');
        } catch (ResolutionException $exception) {
            $this->assertInstanceOf(RuntimeException::class, $exception->getPrevious());
            $this->assertSame('definition lookup failure', $exception->getPrevious()->getMessage());
        }
    }

    /** @throws Throwable When fixture setup or the operation under test throws */
    public function testRejectsAnnotatedClassWithoutTheDefinerContract(): void
    {
        $factory = self::factory([
            Dependency::class => [
                new AttributeMetadata(new Definer()),
            ],
        ]);

        try {
            $factory->get('service');
            $this->fail('Expected invalid definer metadata to fail.');
        } catch (ResolutionException $exception) {
            $this->assertInstanceOf(InvalidArgumentException::class, $exception->getPrevious());
            $this->assertStringContainsString('instantiable implementation', $exception->getPrevious()->getMessage());
        }
    }

    /**
     * @param array<class-string, list<AttributeMetadata<object>>> $classes
     *
     * @throws Throwable When fixture setup or a supplied test callback fails
     */
    private static function factory(array $classes): DefinitionFactoryInterface
    {
        $factory = ContainerFactory::create($classes)->get(DefinitionFactoryInterface::class);

        self::assertInstanceOf(DefinitionFactoryInterface::class, $factory);

        return $factory;
    }

    private static function resetDefiners(): void
    {
        HighPriorityDefiner::$supportCalls = 0;
        HighPriorityDefiner::$getCalls = 0;
        LowPriorityDefiner::$supportCalls = 0;
        LowPriorityDefiner::$getCalls = 0;
    }
}
