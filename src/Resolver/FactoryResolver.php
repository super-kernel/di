<?php
declare(strict_types=1);

namespace SuperKernel\Di\Resolver;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use SuperKernel\Di\Annotation\Resolver;
use SuperKernel\Di\Contract\ResolverFactoryInterface;
use SuperKernel\Di\Definition\FactoryDefinition;
use SuperKernel\Di\Definition\ObjectDefinition;
use SuperKernel\Di\Definition\ParameterDefinition;
use SuperKernel\Di\Contract\DefinitionInterface;
use SuperKernel\Di\Contract\ResolverInterface;

#[Resolver]
final class FactoryResolver implements ResolverInterface
{
	private ?ResolverFactoryInterface $resolverDispatcher = null {
		get => $this->resolverDispatcher ??= $this->container->get(ResolverFactoryInterface::class);
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
	 * @param array             $parameters
	 *
	 * @return mixed
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 */
	public function resolve(DefinitionInterface $definition, array $parameters = []): mixed
	{
		$classname           = $definition->getClassname();
		$name                = $definition->getName();
		$objectDefinition    = new ObjectDefinition($name, $classname);
		$object              = $this->resolverDispatcher->getResolver($objectDefinition)->resolve($objectDefinition, $parameters);
		$parameterDefinition = new ParameterDefinition($classname, '__invoke');
		$arguments           = $this->resolverDispatcher->getResolver($parameterDefinition)->resolve($parameterDefinition, $parameters);

		return $object(...$arguments);
	}
}