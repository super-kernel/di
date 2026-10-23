<?php

declare(strict_types=1);

namespace SuperKernel\Di\Resolver;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use SuperKernel\Attribute\Resolver;
use SuperKernel\Contract\ContainerInterface;
use SuperKernel\Di\Contract\DefinitionInterface;
use SuperKernel\Di\Contract\ResolverFactoryInterface;
use SuperKernel\Di\Contract\ResolverInterface;
use SuperKernel\Di\Definition\FactoryDefinition;
use SuperKernel\Di\Definition\MethodDefinition;
use SuperKernel\Di\Exception\ResolutionException;
use Throwable;

use function array_is_list;
use function get_debug_type;
use function is_array;

/**
 * Resolves factory definitions through a container-owned callable factory.
 */
#[Resolver(priority: 400)]
final class FactoryResolver implements ResolverInterface
{
    private ResolverFactoryInterface $resolverFactory {
        /**
         * @throws ContainerExceptionInterface When the reflector is missing or cannot be resolved
         */
        get {
            if (!isset($this->resolverFactory)) {
                $this->resolverFactory = $this->container->get(ResolverFactoryInterface::class);
            }
            return $this->resolverFactory;
        }
    }

    public function __construct(private readonly ContainerInterface $container)
    {
    }

    public function support(DefinitionInterface $definition): bool
    {
        return $definition instanceof FactoryDefinition;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function resolve(DefinitionInterface $definition): mixed
    {
        if (!$definition instanceof FactoryDefinition) {
            throw ResolutionException::unsupportedDefinition($definition);
        }

        try {
            $factory = $this->container->get($definition->factoryClass);
            $arguments = $this->resolverFactory->resolve(new MethodDefinition(
                $definition->factoryClass,
                '__invoke',
            ));

            if (!is_array($arguments) || !array_is_list($arguments)) {
                throw ResolutionException::invalidMethodArguments(
                    $definition->factoryClass,
                    '__invoke',
                    get_debug_type($arguments),
                );
            }

            return $factory(...$arguments);
        } catch (ContainerExceptionInterface $exception) {
            throw $exception;
        } catch (Throwable $throwable) {
            throw ResolutionException::factoryInvocationFailed(
                $definition->factoryClass,
                $definition->className,
                $throwable,
            );
        }
    }
}
