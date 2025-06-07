<?php
declare(strict_types=1);

namespace SuperKernel\Di\Contract;

/**
 * Define Factory.
 *
 * @DefinitionFactoryInterface
 * @\SuperKernel\Di\Contract\DefinitionFactoryInterface
 */
interface DefinitionFactoryInterface
{
	public function getDefinition(string $id): ?DefinitionInterface;

	public function getResolver(DefinitionInterface $definition): ?ResolverInterface;
}