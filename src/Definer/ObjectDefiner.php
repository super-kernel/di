<?php
declare(strict_types=1);

namespace SuperKernel\Di\Definer;

use SuperKernel\Di\Abstract\DefinerAbstract;
use SuperKernel\Di\Annotation\Definer;
use SuperKernel\Di\Contract\DefinerInterface;
use SuperKernel\Di\Contract\DefinitionInterface;
use SuperKernel\Di\Definition\ObjectDefinition;
use SuperKernel\Di\Exception\InvalidDefinitionException;

#[Definer(priority: 1)]
final class ObjectDefiner extends DefinerAbstract implements DefinerInterface
{
	/**
	 * @param string $id
	 *
	 * @return bool
	 */
	public function support(string $id): bool
	{
		return class_exists($id) || interface_exists($id);
	}

	/**
	 * @param string $id
	 *
	 * @return DefinitionInterface
	 * @throws InvalidDefinitionException
	 */
	public function create(string $id): DefinitionInterface
	{
		if (!interface_exists($id)) {
			return new ObjectDefinition($id);
		}

		$classname = $this->getRealEntry($id);

		if (null === $classname) {
			throw new InvalidDefinitionException("No definition found for entry $id");
		}

		if (is_subclass_of($classname, $id)) {
			return new ObjectDefinition($id, $classname);
		}

		throw new InvalidDefinitionException(
			sprintf('The %s interface dependency mapped by %s class does not implement the interface.', $id, $classname));
	}
}