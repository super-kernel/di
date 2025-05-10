<?php
declare(strict_types=1);

namespace SuperKernel\Di\Abstract;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use SuperKernel\Di\Interface\DefinitionInterface;
use SuperKernel\Di\Interface\ResolverInterface;

/**
 * @AbstractResolverDispatcher
 * @\SuperKernel\Di\Abstract\AbstractResolverDispatcher
 */
abstract class AbstractResolverDispatcher implements ResolverInterface
{
	private array $resolvers = [];

	public function __construct(private readonly ContainerInterface $container, array $resolverFactories = [])
	{
		foreach ($resolverFactories as $definition => $resolver) {
			if (is_a($definition, DefinitionInterface::class, true) && is_a($resolver, ResolverInterface::class, true)) {
				$this->resolvers[$definition] = new $resolver($container, $this);
			}
		}
	}

	/**
	 * @param DefinitionInterface $definition
	 * @param array               $parameters
	 *
	 * @return mixed
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 */
	public function resolve(DefinitionInterface $definition, array $parameters = []): mixed
	{
		foreach ($this->resolvers as $resolver) {
			if ($resolver->support($definition)) {
				return $resolver->resolve($definition, $parameters);
			}
		}

		return $this->container->get((string)$definition);
	}
}