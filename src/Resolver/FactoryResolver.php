<?php
declare(strict_types=1);

namespace SuperKernel\Di\Resolver;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use SuperKernel\Di\Definition\FactoryDefinition;
use SuperKernel\Di\Definition\ParameterDefinition;
use SuperKernel\Di\Contract\DefinitionInterface;
use SuperKernel\Di\Contract\ResolverInterface;
use SuperKernel\Di\Factory\ResolverFactory;

/**
 * @FactoryResolver
 * @\SuperKernel\Di\Resolver\FactoryResolver
 */
final readonly class FactoryResolver implements ResolverInterface
{
	private ResolverInterface $resolverDispatcher;

	public function __construct(private ContainerInterface $container)
	{
		$this->resolverDispatcher = $this->container->get(ResolverFactory::class);
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
		$classname  = $definition->getClassname();
		$object     = $this->container->get($classname);
		$arguments  = $this->resolverDispatcher->resolve(new ParameterDefinition($classname, '__invoke'), $parameters);
		$parameters = array_merge($arguments, $parameters);

		return $object(...$parameters);
	}
}