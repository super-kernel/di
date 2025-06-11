<?php
declare(strict_types=1);

namespace SuperKernel\Di\Definer;

use SuperKernel\Di\Annotation\Definer;
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
	 * @return DefinitionInterface|null
	 */
	public function getDefinition(string $id): ?DefinitionInterface
	{
		if (class_exists($id) || interface_exists($id)) {
			return new ObjectDefinition($id);
		}

		return null;
	}
}