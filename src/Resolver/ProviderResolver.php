<?php
declare(strict_types=1);

namespace SuperKernel\Di\Resolver;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use SuperKernel\Di\Attribute\Resolver;
use SuperKernel\Di\Contract\DefinitionInterface;
use SuperKernel\Di\Contract\ResolverInterface;
use SuperKernel\Di\Definition\ProviderDefinition;
use SuperKernel\Di\Exception\Container\ResolverException;

#[Resolver]
final readonly class ProviderResolver implements ResolverInterface
{
	public function __construct(private ContainerInterface $container)
	{
	}

	/**
	 * @param DefinitionInterface $definition
	 *
	 * @return bool
	 */
	public function support(DefinitionInterface $definition): bool
	{
		return $definition instanceof ProviderDefinition;
	}

	/**
	 * @param DefinitionInterface $definition
	 *
	 * @return object
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 */
	public function resolve(DefinitionInterface $definition): object
	{
		if (!($definition instanceof ProviderDefinition)) {
			throw ResolverException::unsupportedDefinition($definition);
		}

		return $this->container->get($definition->getClassName());
	}
}