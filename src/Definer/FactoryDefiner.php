<?php
declare(strict_types=1);

namespace SuperKernel\Di\Definer;

use ReflectionException;
use SuperKernel\Di\Annotation\Definer;
use SuperKernel\Di\Annotation\Factory;
use SuperKernel\Di\Collector\ReflectionManager;
use SuperKernel\Di\Contract\DefinerInterface;
use SuperKernel\Di\Contract\DefinitionInterface;
use SuperKernel\Di\Definition\FactoryDefinition;

#[Definer]
final class FactoryDefiner implements DefinerInterface
{
	/**
	 * @param string $id
	 *
	 * @return DefinitionInterface
	 */
	public function getDefinition(string $id): DefinitionInterface
	{
		return new FactoryDefinition($id);
	}

	/**
	 * @param string $id
	 *
	 * @return bool
	 */
	public function support(string $id): bool
	{
		try {
			$reflectClass = ReflectionManager::reflectClass($id);
			$attributes   = $reflectClass->getAttributes();

			return array_any($attributes, fn($attribute) => $attribute->getName() === Factory::class);
		}
		catch (ReflectionException) {
			return false;
		}
	}
}