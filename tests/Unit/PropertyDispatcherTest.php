<?php

declare(strict_types=1);

namespace SuperKernel\Di\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use ReflectionClass;
use ReflectionException;
use SuperKernel\Annotator\Model\AttributeMetadata;
use SuperKernel\Attribute\Autowired;
use SuperKernel\Attribute\PropertyHandler;
use SuperKernel\Contract\AnnotationCollectorInterface;
use SuperKernel\Contract\AttributeMetadataInterface;
use SuperKernel\Contract\ContainerInterface;
use SuperKernel\Contract\ReflectorInterface;
use SuperKernel\Di\Exception\NotFoundException;
use SuperKernel\Di\Property\PropertyContext;
use SuperKernel\Di\Property\PropertyDispatcher;
use SuperKernel\Di\Tests\Fixture\ThirdPartyPropertyAttribute;
use SuperKernel\Di\Tests\Fixture\ThirdPartyPropertyHandler;
use SuperKernel\Di\Tests\Fixture\ThirdPartyPropertyService;

final class PropertyDispatcherTest extends TestCase
{
    /** @return iterable<string, array{bool}> */
    public static function metadataImplementations(): iterable
    {
        yield 'Annotator models' => [
            false,
        ];
        yield 'independent metadata' => [
            true,
        ];
    }

    /**
     * @throws ContainerExceptionInterface When a dispatcher dependency cannot be resolved
     * @throws ReflectionException         When the fixture property cannot be reflected
     */
    #[DataProvider('metadataImplementations')]
    public function testUsesTheContainerReflectorForAttributesAndHandlersOnFirstUse(bool $independent): void
    {
        $collector = $this->collector($independent);
        $reflector = $this->createMock(ReflectorInterface::class);
        $reflector->expects($this->exactly(2))->method('reflectClass')->willReturnMap([
            [
                ThirdPartyPropertyAttribute::class,
                new ReflectionClass(ThirdPartyPropertyAttribute::class),
            ],
            [
                ThirdPartyPropertyHandler::class,
                new ReflectionClass(ThirdPartyPropertyHandler::class),
            ],
        ]);
        $lookups = [];
        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->exactly(2))->method('get')->willReturnCallback(
            static function (string $identifier) use ($collector, $reflector, &$lookups): object {
                $lookups[] = $identifier;

                return match ($identifier) {
                    AnnotationCollectorInterface::class => $collector,
                    ReflectorInterface::class           => $reflector,
                    default                             => self::fail('Unexpected container lookup: ' . $identifier),
                };
            },
        );
        $dispatcher = new PropertyDispatcher($container);

        $this->assertSame([], $lookups);
        $this->assertTrue($dispatcher->supports(ThirdPartyPropertyAttribute::class));
        $this->assertFalse($dispatcher->supports(Autowired::class));

        $property = new ReflectionClass(ThirdPartyPropertyService::class)->getProperty('handled');
        $context = new PropertyContext(ThirdPartyPropertyService::class, $property);
        $dispatcher->dispatch($context, $property->getAttributes()[0]);

        $this->assertSame('handled', $context->assignment());
        $this->assertSame([
            AnnotationCollectorInterface::class,
            ReflectorInterface::class,
        ], $lookups);
    }

    /**
     * @throws ContainerExceptionInterface When the annotation collector cannot be resolved
     */
    public function testDoesNotResolveTheReflectorWhenNoHandlersAreRegistered(): void
    {
        $collector = $this->createStub(AnnotationCollectorInterface::class);
        $collector->method('getClassesWithAttribute')->willReturn([]);
        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->once())->method('get')
            ->with(AnnotationCollectorInterface::class)->willReturn($collector);
        $dispatcher = new PropertyDispatcher($container);

        $this->assertFalse($dispatcher->supports(ThirdPartyPropertyAttribute::class));
        $this->assertFalse($dispatcher->supports(Autowired::class));
    }

    public function testPreservesContainerExceptionsWhenTheReflectorCannotBeResolved(): void
    {
        $collector = $this->collector();
        $exception = NotFoundException::forIdentifier(ReflectorInterface::class);
        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->exactly(2))->method('get')->willReturnCallback(
            static fn(string $identifier): object => match ($identifier) {
                AnnotationCollectorInterface::class => $collector,
                ReflectorInterface::class           => throw $exception,
                default                             => self::fail('Unexpected container lookup: ' . $identifier),
            },
        );

        try {
            $this->assertTrue(new PropertyDispatcher($container)->supports(ThirdPartyPropertyAttribute::class));
            $this->fail('Expected reflector lookup to fail.');
        } catch (ContainerExceptionInterface $caught) {
            $this->assertSame($exception, $caught);
        }
    }

    private function collector(bool $independent = false): AnnotationCollectorInterface
    {
        $declaration = new PropertyHandler(ThirdPartyPropertyAttribute::class);
        if ($independent) {
            $metadata = $this->createStub(AttributeMetadataInterface::class);
            $metadata->method('attribute')->willReturn($declaration);
        } else {
            $metadata = new AttributeMetadata($declaration);
        }
        $collector = $this->createStub(AnnotationCollectorInterface::class);
        $collector->method('getClassesWithAttribute')->willReturn([
            ThirdPartyPropertyHandler::class => [
                $metadata,
            ],
        ]);

        return $collector;
    }
}
