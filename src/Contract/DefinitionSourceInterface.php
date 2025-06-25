<?php
declare(strict_types=1);

namespace SuperKernel\Di\Contract;

interface DefinitionSourceInterface
{
	public function getDefinition(string $name): ?DefinitionInterface;

	public function hasDefinition(string $name): bool;

	public function setDefinition(DefinitionInterface $definition);
}