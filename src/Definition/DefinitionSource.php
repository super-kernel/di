<?php
declare(strict_types=1);

namespace SuperKernel\Di\Definition;

use SuperKernel\Di\Contract\DefinitionInterface;
use SuperKernel\Di\Contract\DefinitionSourceInterface;

final class DefinitionSource implements DefinitionSourceInterface
{
	private array $definitions = [];

	public function __construct()
	{
	}

	public function getDefinition(string $name): ?DefinitionInterface
	{
		if (isset($this->definitions[$name]) || array_key_exists($name, $this->definitions)) {
			return $this->definitions[$name];
		}

		if (class_exists($name) || interface_exists($name)) {
			return new ObjectDefinition($name);
		}

		return null;
	}

	public function hasDefinition(string $name): bool
	{
	}

	public function setDefinition(DefinitionInterface $definition)
	{
	}
}