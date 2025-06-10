<?php
declare(strict_types=1);

namespace SuperKernel\Di\Definer;

use SuperKernel\Di\Annotation\Definer;
use SuperKernel\Di\Contract\DefinitionInterface;
use SuperKernel\Di\Definition\ObjectDefinition;

#[Definer(priority: 1)]
final class ObjectDefiner
{
	public function __construct()
	{
	}

	/**
	 * @param mixed $name
	 *
	 * @return DefinitionInterface|null
	 */
	public function __invoke(mixed $name): ?DefinitionInterface
	{
		if (is_string($name) && class_exists($name) && interface_exists($name)) {
			return new ObjectDefinition($name);
		}

		return null;
	}
}