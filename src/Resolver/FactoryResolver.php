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
use SuperKernel\Di\Definition\MethodDefinition;
use SuperKernel\Di\Contract\DefinitionInterface;
use SuperKernel\Di\Contract\ResolverInterface;
use SuperKernel\Di\Exception\Container\ResolverException;
use function method_exists;

#[Resolver]
final class FactoryResolver implements ResolverInterface
{
	private ResolverFactoryInterface $resolverFactory {
		/**
		 * @throws ContainerExceptionInterface
		 * @throws NotFoundExceptionInterface
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

		$classname = $definition->getClassName();

		$objectDefinition = new ObjectDefinition($classname);
		$object = $this->resolverFactory->getResolver($objectDefinition)->resolve($objectDefinition);

		if (!method_exists($object, '__invoke')) {
			throw ResolverException::factoryNotResolvable($definition);
		}

		$parameterDefinition = new MethodDefinition($classname, '__invoke');
		$arguments = $this->resolverFactory->getResolver($parameterDefinition)->resolve($parameterDefinition);

		return $object(...$arguments);
	}
}