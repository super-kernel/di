<?php

declare(strict_types=1);

namespace SuperKernel\Di\Resolver;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use SuperKernel\Attribute\Resolver;
use SuperKernel\Contract\ContainerInterface;
use SuperKernel\Di\Contract\DefinitionInterface;
use SuperKernel\Di\Contract\ResolverInterface;
use SuperKernel\Di\Definition\ProviderDefinition;
use SuperKernel\Di\Exception\ResolutionException;
use Throwable;

/**
 * Resolves provider definitions through the container-owned provider class.
 */
#[Resolver(priority: 300)]
final readonly class ProviderResolver implements ResolverInterface
{
    public function __construct(private ContainerInterface $container)
    {
    }

    public function support(DefinitionInterface $definition): bool
    {
        return $definition instanceof ProviderDefinition;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function resolve(DefinitionInterface $definition): mixed
    {
        if (!$definition instanceof ProviderDefinition) {
            throw ResolutionException::unsupportedDefinition($definition);
        }

        try {
            return $this->container->get($definition->providerClass);
        } catch (ContainerExceptionInterface $exception) {
            throw $exception;
        } catch (Throwable $throwable) {
            throw ResolutionException::providerResolutionFailed(
                $definition->providerClass,
                $definition->className,
                $throwable,
            );
        }
    }
}
