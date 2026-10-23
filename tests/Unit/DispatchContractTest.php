<?php

declare(strict_types=1);

namespace SuperKernel\Di\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use SuperKernel\Contract\ContainerInterface;
use SuperKernel\Di\Contract\DefinerInterface;
use SuperKernel\Di\Contract\DefinitionFactoryInterface;
use SuperKernel\Di\Contract\DefinitionInterface;
use SuperKernel\Di\Contract\ResolverFactoryInterface;
use SuperKernel\Di\Contract\ResolverInterface;
use Throwable;

use function array_map;
use function sort;

final class DispatchContractTest extends TestCase
{
    /**
     * @return iterable<string, array{class-string, list<string>}>
     */
    public static function interfaceMethods(): iterable
    {
        yield 'definition' => [
            DefinitionInterface::class,
            [
                'identifier',
            ],
        ];
        yield 'definer' => [
            DefinerInterface::class,
            [
                'get',
                'support',
            ],
        ];
        yield 'definition factory' => [
            DefinitionFactoryInterface::class,
            [
                'get',
            ],
        ];
        yield 'resolver' => [
            ResolverInterface::class,
            [
                '__construct',
                'resolve',
                'support',
            ],
        ];
        yield 'resolver factory' => [
            ResolverFactoryInterface::class,
            [
                'resolve',
                'support',
            ],
        ];
    }

    /**
     * @param class-string $interface
     * @param list<string> $expectedMethods
     *
     * @throws Throwable When fixture setup or the operation under test throws
     */
    #[DataProvider('interfaceMethods')]
    public function testExposesTheMinimalDispatchContract(string $interface, array $expectedMethods): void
    {
        $methods = array_map(
            static fn(ReflectionMethod $method): string => $method->getName(),
            new ReflectionClass($interface)->getMethods(),
        );
        sort($methods);

        $this->assertSame($expectedMethods, $methods);
    }

    public function testResolverReceivesItsContainerThroughTheContract(): void
    {
        $constructor = new ReflectionClass(ResolverInterface::class)->getConstructor();

        $this->assertNotNull($constructor);
        $this->assertTrue($constructor->isPublic());
        $this->assertSame(1, $constructor->getNumberOfRequiredParameters());
        $this->assertSame(1, $constructor->getNumberOfParameters());
        $this->assertSame(
            ContainerInterface::class,
            (string) $constructor->getParameters()[0]->getType(),
        );
    }
}
