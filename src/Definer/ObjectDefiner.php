<?php
declare(strict_types=1);

namespace SuperKernel\Di\Definer;

use ReflectionException;
use SuperKernel\Di\Annotation\Definer;
use SuperKernel\Di\Collector\ReflectionManager;
use SuperKernel\Di\Contract\DefinerInterface;
use SuperKernel\Di\Contract\DefinitionInterface;
use SuperKernel\Di\Definition\ObjectDefinition;

#[Definer(priority: 1)]
final class ObjectDefiner implements DefinerInterface
{
	public function __construct()
	{
	}

	/**
	 * @param string $id
	 *
	 * @return DefinitionInterface
	 */
	public function getDefinition(string $id): DefinitionInterface
	{
		return new ObjectDefinition($id);
	}

	/**
	 * @param string $id
	 *
	 * @return bool
	 */
	public function support(string $id): bool
	{
		try {
			return ReflectionManager::reflectClass($id)->isInstantiable();
		}
		catch (ReflectionException) {
			return false;
		}
	}
}