<?php
declare(strict_types=1);

namespace SuperKernel\Di\Definer;

use ReflectionAttribute;
use SuperKernel\Di\Annotation\Definer;
use SuperKernel\Di\Annotation\Factory;
use SuperKernel\Di\Collector\ReflectionManager;
use SuperKernel\Di\Contract\DefinerInterface;
use SuperKernel\Di\Contract\DefinitionInterface;
use SuperKernel\Di\Definition\FactoryDefinition;

#[Definer]
final class FactoryDefiner implements DefinerInterface
{
	private ReflectionManager $reflectionManager;

	public function __construct()
	{
		$this->reflectionManager = new ReflectionManager()();
	}

	/**
	 * @param string $id
	 *
	 * @return DefinitionInterface|null
	 */
	public function getDefinition(string $id): ?DefinitionInterface
	{
		$attributes = $this->reflectionManager->reflectClass($id)?->getAttributes() ?? [];

		/* @var ReflectionAttribute $attribute */
		if (array_any($attributes, fn($attribute) => Factory::class === $attribute->getName())) {
			return new FactoryDefinition($id);
		}

		return null;
	}
}