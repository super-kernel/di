<?php
declare(strict_types=1);

namespace SuperKernel\Di\Resolver;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use SuperKernel\Di\Definition\FactoryDefinition;
use SuperKernel\Di\Interface\DefinitionInterface;
use SuperKernel\Di\Interface\ResolverInterface;

/**
 * @FactoryResolver
 * @\SuperKernel\Di\Resolver\FactoryResolver
 */
final readonly class FactoryResolver implements ResolverInterface
{
	/**
	 * @param ContainerInterface $container
	 */
	public function __construct(private ContainerInterface $container)
	{
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
		$arguments  = $this->container->make(sprintf('%s::%s', $classname, '__invoke'), $parameters);
		$parameters = array_merge($arguments, $parameters);

		return $object(...$parameters);
	}
}