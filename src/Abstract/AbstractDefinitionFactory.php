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
		$this->normalizeDefinition($dependencies);
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

	private function normalizeDefinition(array $dependencies): void
	{
		foreach ($dependencies as $id => $definition) {
			if (is_string($id) && is_string($definition) && class_exists($definition)) {
				$this->definitions[$id] = $this->autowire($id, new ObjectDefinition($id, $definition));
				continue;
			}
			if ('factories' === $id && is_array($definition)) {
				foreach ($definition as $name => $definitionFactory) {
					if (is_string($name) && is_string($definitionFactory) && class_exists($definitionFactory)) {
						$this->definitions[$name] = new FactoryDefinition($name, $definitionFactory);
					}
				}
				continue;
			}

			$this->definitions[$id] = null;
		}
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