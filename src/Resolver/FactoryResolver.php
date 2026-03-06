<?php
declare(strict_types=1);

namespace SuperKernel\Di\Resolver;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use SuperKernel\Di\Attribute\Resolver;
use SuperKernel\Di\Contract\ResolverFactoryInterface;
use SuperKernel\Di\Definition\FactoryDefinition;
use SuperKernel\Di\Definition\ObjectDefinition;
use SuperKernel\Di\Definition\ParameterDefinition;
use SuperKernel\Di\Contract\DefinitionInterface;
use SuperKernel\Di\Contract\ResolverInterface;
use SuperKernel\Di\Exception\Container\ResolverException;

#[Resolver]
final class FactoryResolver implements ResolverInterface
{
	private ResolverFactoryInterface $resolverDispatcher {
		/**
		 * @throws ContainerExceptionInterface
		 * @throws NotFoundExceptionInterface
		 */
		get {
			if (!isset($this->resolverDispatcher)) {
				$this->resolverDispatcher = $this->container->get(ResolverFactoryInterface::class);
			}
			return $this->resolverDispatcher;
		}
	}

	public function __construct(private readonly ContainerInterface $container)
	{
	}

	/**
	 * @param DefinitionInterface $definition
	 *
	 * @return bool
	 */
	public function support(DefinitionInterface $definition): bool
	{
		return $definition instanceof FactoryDefinition;
	}

	/**
	 * @param FactoryDefinition $definition
	 *
	 * @return mixed
	 */
	public function resolve(DefinitionInterface $definition): mixed
	{
		if (!($definition instanceof FactoryDefinition)) {
			throw ResolverException::unsupportedDefinition($definition);
		}

		$classname = $definition->getName();
		$objectDefinition = new ObjectDefinition($classname);
		$object = $this->resolverDispatcher->getResolver($objectDefinition)->resolve($objectDefinition);
		$parameterDefinition = new ParameterDefinition($classname, '__invoke');
		$arguments = $this->resolverDispatcher->getResolver($parameterDefinition)->resolve($parameterDefinition);

		return $object(...$arguments);
	}
}