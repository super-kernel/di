<?php
declare(strict_types=1);

namespace SuperKernel\Di\Definer;

use ReflectionAttribute;
use SuperKernel\Di\Annotation\Definer;
use SuperKernel\Di\Annotation\Factory;
use SuperKernel\Di\Collector\ReflectionManager;
use SuperKernel\Di\Container;
use SuperKernel\Di\Contract\DefinitionInterface;
use SuperKernel\Di\Definition\FactoryDefinition;
use SuperKernel\Di\Exception\NotFoundException;

#[Definer]
final class FactoryDefiner
{
	private ReflectionManager $reflectionManager;

	/**
	 * @param Container $container
	 *
	 * @throws NotFoundException
	 */
	public function __construct(private readonly Container $container)
	{
		$this->reflectionManager = $this->container->get(ReflectionManager::class);
	}

	/**
	 * @param mixed $name
	 *
	 * @return DefinitionInterface|null
	 */
	public function __invoke(mixed $name): ?DefinitionInterface
	{
		if (is_string($name)) {
			$attributes = $this->reflectionManager->reflectClass($name)?->getAttributes() ?? [];

			/* @var ReflectionAttribute $attribute */
			if (array_any($attributes, fn($attribute) => Factory::class === $attribute->getName())) {
				return new FactoryDefinition($name);
			}
		}

		return null;
	}
}