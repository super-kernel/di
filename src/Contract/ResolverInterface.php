<?php
declare(strict_types=1);

namespace SuperKernel\Di\Contract;

interface ResolverInterface
{
	public function support(DefinitionInterface $definition): bool;

	public function resolve(DefinitionInterface $definition): mixed;
}