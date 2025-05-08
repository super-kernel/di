<?php
declare(strict_types=1);

namespace SuperKernel\Di\Interface;

use SuperKernel\Di\Interface\Visitor\DefinitionVisitorInterface;

/**
 * @ContainerDispatcherInterface
 * @\SuperKernel\Di\Interface\ContainerDispatcherInterface
 */
interface ContainerDispatcherInterface
{
	public function register(
		DefinitionVisitorInterface $definitionVisitor,
		ResolverInterface          $resolver,
		int                        $priority = 0,
	): void;

	public function getDefinition(string $id): ?DefinitionInterface;

	public function getResolver(DefinitionInterface $definition): ?ResolverInterface;
}