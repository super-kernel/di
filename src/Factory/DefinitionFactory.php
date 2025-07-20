<?php
declare(strict_types=1);

namespace SuperKernel\Di\Factory;

use SuperKernel\Di\Contract\DefinerFactoryInterface;
use SuperKernel\Di\Contract\DefinitionFactoryInterface;
use SuperKernel\Di\Contract\DefinitionInterface;

final class DefinitionFactory implements DefinitionFactoryInterface
{
	private array $definitions = [];

	public function __construct(private readonly DefinerFactoryInterface $definerFactory)
	{
	}

	public function getDefinition(string $id): ?DefinitionInterface
	{
		return $this->definitions[$id] ??= $this->definerFactory->getDefinition($id);
	}

	public function getDefinitions(): array
	{
		return $this->definitions;
	}

	public function hasDefinition(string $id): bool
	{
		if (isset($this->definitions[$id]) || array_key_exists($id, $this->definitions)) {
			return true;
		}

		return false;
	}

	public function setDefinition(string $id, ?string $definition): ?DefinitionInterface
	{
		return $this->definitions[$id] = $this->definerFactory->getDefinition($definition ?? $id);
	}
}