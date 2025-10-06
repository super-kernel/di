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
}