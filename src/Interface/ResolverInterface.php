<?php
declare(strict_types=1);

namespace SuperKernel\Di\Interface;

/**
 * @ResolverInterface
 * @\SuperKernel\Di\Interface\ResolverInterface
 */
interface ResolverInterface
{
	public function resolve(DefinitionInterface $definition, array $parameters = []): mixed;
}