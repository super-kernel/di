<?php
declare (strict_types=1);

namespace SuperKernel\Di\Abstract;

use SuperKernel\Di\Definition\FactoryDefinition;
use SuperKernel\Di\Definition\ObjectDefinition;
use SuperKernel\Di\Interface\DefinitionFactoryInterface;
use SuperKernel\Di\Interface\DefinitionInterface;

/**
 * @AbstractDefinitionFactory
 * @\SuperKernel\Di\AbstractDefinitionFactory
 */
abstract class AbstractDefinitionFactory implements DefinitionFactoryInterface
{
	private array $definitions = [];

	public function __construct(array $dependencies = [])
	{
		foreach ($dependencies as $id => $definition) {
			$this->definitions[$id] = $this->normalizeDefinition($id, $definition);
		}
	}

	/**
	 * @param string $id
	 *
	 * @return DefinitionInterface|null
	 */
	public function getDefinition(string $id): ?DefinitionInterface
	{
		return $this->definitions[$id] ??= $this->autowire($id);
	}

	private function normalizeDefinition(string $id, callable|array|string $definition): ?DefinitionInterface
	{
		if (is_string($definition) && class_exists($definition)) {
			if (method_exists($definition, '__invoke')) {
				return new FactoryDefinition($id, $definition);
			}
			return $this->autowire($id, new ObjectDefinition($id, $definition));
		}

		return null;
	}

	private function autowire(string $name, ?DefinitionInterface $definition = null): ?DefinitionInterface
	{
		$className = $definition ? $definition->getClassName() : $name;
		if (!class_exists($className) && !interface_exists($className)) {
			return $definition;
		}

		return $definition ?: new ObjectDefinition($name);
	}
}