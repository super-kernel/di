<?php
declare(strict_types=1);

namespace SuperKernel\Di\Contract;

interface ResolverFactoryInterface
{
	/**
	 * @param DefinitionInterface $definition
	 *
	 * @return ResolverInterface
	 */
	public function getResolver(DefinitionInterface $definition): ResolverInterface;

	public function setResolver(string $resolver, int $priority = 0): void;

	public function setContainer(ContainerInterface $container): void;
}