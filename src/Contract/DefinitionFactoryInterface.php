<?php
declare(strict_types=1);

namespace SuperKernel\Di\Contract;

interface DefinitionFactoryInterface
{
	public function getDefinition(string $id): ?DefinitionInterface;

	public function getDefinitions(): array;

	public function hasDefinition(string $id): bool;

	public function setDefinition(string $id, ?string $definition): ?DefinitionInterface;
}