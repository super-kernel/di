<?php
declare(strict_types=1);

namespace SuperKernel\Di\Resolver;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use SuperKernel\Di\Attribute\Resolver;
use SuperKernel\Di\Contract\DefinitionInterface;
use SuperKernel\Di\Contract\ResolverInterface;
use SuperKernel\Di\Definition\InterfaceDefinition;

#[Resolver]
final readonly class InterfaceResolver implements ResolverInterface
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
		return $definition instanceof InterfaceDefinition;
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
		return $this->container->get($definition->getName());
	}
}