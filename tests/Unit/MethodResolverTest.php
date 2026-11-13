<?php

declare(strict_types=1);

namespace SuperKernel\Di\Tests\Unit;

use Closure;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use SuperKernel\Annotator\Model\AttributeMetadata;
use SuperKernel\Attribute\Definer;
use SuperKernel\Di\Container;
use SuperKernel\Di\Contract\ResolverFactoryInterface;
use SuperKernel\Di\Definition\ClassDefinition;
use SuperKernel\Di\Definition\MethodDefinition;
use SuperKernel\Di\Exception\ResolutionException;
use SuperKernel\Di\Resolver\MethodResolver;
use SuperKernel\Di\Tests\Fixture\ContainerFactory;
use SuperKernel\Di\Tests\Fixture\Dependency;
use SuperKernel\Di\Tests\Fixture\MethodArgumentService;
use SuperKernel\Di\Tests\Fixture\NullableUnloadableDependencyService;
use SuperKernel\Di\Tests\Fixture\Provider\MethodInjectedProvider;
use SuperKernel\Di\Tests\Fixture\ReferenceMethodService;
use SuperKernel\Di\Tests\Fixture\RequiredContractService;
use SuperKernel\Di\Tests\Fixture\Service;
use SuperKernel\Di\Tests\Fixture\ThrowingDefiner;
use SuperKernel\Di\Tests\Fixture\UnloadableDependency;
use SuperKernel\Di\Tests\Fixture\UnloadableDependencyService;
use SuperKernel\Di\Tests\Fixture\UnloadableOrAvailableDependencyService;
use Throwable;

use function class_exists;
use function spl_autoload_register;
use function spl_autoload_unregister;

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

    /** @throws Throwable When fixture setup or the operation under test throws */
    public function testKeepsTheLoadFailureOfAParameterTypeAsTheCause(): void
    {
        $this->whileUnloadable(function (): void {
            try {
                $this->createContainer()->get(UnloadableDependencyService::class);
                $this->fail('Expected a resolution exception.');
            } catch (ResolutionException $exception) {
                $this->assertSame(
                    'Unable to resolve method parameter ' . UnloadableDependencyService::class
                    . '::__construct($dependency) of type ' . UnloadableDependency::class . '.',
                    $exception->getMessage(),
                );

                $lookupFailure = $exception->getPrevious();
                $this->assertInstanceOf(ResolutionException::class, $lookupFailure);
                $this->assertSame(
                    'Unable to obtain a definition for container entry "' . UnloadableDependency::class . '".',
                    $lookupFailure->getMessage(),
                );

                $loadFailure = $lookupFailure->getPrevious();
                $this->assertInstanceOf(RuntimeException::class, $loadFailure);
                $this->assertSame('aspect weaving failure', $loadFailure->getMessage());
            }
        });
    }

    /** @throws Throwable When fixture setup or the operation under test throws */
    public function testKeepsADefinerFailureAsTheCauseOfAnUnresolvableParameter(): void
    {
        $container = ContainerFactory::create([
            ThrowingDefiner::class => [
                new AttributeMetadata(new Definer(
                    priority : 300,
                )),
            ],
        ]);

        try {
            new MethodResolver($container)->resolve(new MethodDefinition(RequiredContractService::class));
            $this->fail('Expected a resolution exception.');
        } catch (ResolutionException $exception) {
            $this->assertStringContainsString(
                'RequiredContractService::__construct($dependency)',
                $exception->getMessage(),
            );
            $this->assertInstanceOf(ResolutionException::class, $exception->getPrevious());
            $this->assertSame('definition lookup failure', $exception->getPrevious()->getPrevious()?->getMessage());
        }
    }

    /** @throws Throwable When fixture setup or the operation under test throws */
    public function testAParameterTypeWithoutADefinitionHasNoCause(): void
    {
        try {
            new MethodResolver($this->createContainer())->resolve(new MethodDefinition(RequiredContractService::class));
            $this->fail('Expected a resolution exception.');
        } catch (ResolutionException $exception) {
            $this->assertStringContainsString(
                'RequiredContractService::__construct($dependency)',
                $exception->getMessage(),
            );
            $this->assertNull($exception->getPrevious());
        }
    }

    /** @throws Throwable When fixture setup or the operation under test throws */
    public function testNullableAndUnionParametersStillResolvePastALoadFailure(): void
    {
        $this->whileUnloadable(function (): void {
            $container = $this->createContainer();
            $resolver = new MethodResolver($container);

            $this->assertSame(
                [
                    null,
                ],
                $resolver->resolve(new MethodDefinition(NullableUnloadableDependencyService::class)),
            );
            $this->assertSame(
                [
                    $container->get(Dependency::class),
                ],
                $resolver->resolve(new MethodDefinition(UnloadableOrAvailableDependencyService::class)),
            );
        });
    }

    private function createContainer(): Container
    {
        return ContainerFactory::create();
    }

    /**
     * Runs a test while an autoloader fails to load UnloadableDependency, as a failing aspect weave does.
     *
     * @param Closure(): void $test
     */
    private function whileUnloadable(Closure $test): void
    {
        $this->assertFalse(class_exists(UnloadableDependency::class, false));

        $loader = static function (string $class): void {
            if (UnloadableDependency::class === $class) {
                throw new RuntimeException('aspect weaving failure');
            }
        };

        spl_autoload_register($loader, true, true);

        try {
            $test();
        } finally {
            spl_autoload_unregister($loader);
        }
    }
}
