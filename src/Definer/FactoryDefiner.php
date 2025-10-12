<?php
declare(strict_types=1);

namespace SuperKernel\Di\Definer;

use ReflectionAttribute;
use SuperKernel\Attribute\Factory;
use SuperKernel\Di\Abstract\DefinerAbstract;
use SuperKernel\Di\Annotation\Definer;
use SuperKernel\Di\Collector\ReflectionManager;
use SuperKernel\Di\Contract\DefinerInterface;
use SuperKernel\Di\Contract\DefinitionInterface;
use SuperKernel\Di\Definition\FactoryDefinition;
use SuperKernel\Di\Exception\InvalidDefinitionException;

#[Definer(2)]
final class FactoryDefiner extends DefinerAbstract implements DefinerInterface
{
	/**
	 * @param string $id
	 *
	 * @return bool
	 */
	public function support(string $id): bool
	{
		$classname = $this->getRealEntry($id);

		if (null === $classname) {
			return false;
		}

		if (class_exists($classname) || interface_exists($classname)) {
			return array_any(ReflectionManager::getClassAnnotations($classname), fn(ReflectionAttribute $attribute) => $attribute->getName() === Factory::class);
		}

		return false;
	}

	/**
	 * @param string $id
	 *
	 * @return DefinitionInterface
	 * @throws InvalidDefinitionException
	 */
	public function create(string $id): DefinitionInterface
	{
		$classname = $this->getRealEntry($id);

		if (null === $classname) {
			throw new InvalidDefinitionException("No definition found for entry $id");
		}

		if (method_exists($classname, '__invoke')) {
			return new FactoryDefinition($id, $classname);
		}

		throw new InvalidDefinitionException("The magic method $classname::__invoke() does not exist.");
	}
}