<?php
declare(strict_types=1);

namespace SuperKernel\Di\Definer;

use ReflectionException;
use SuperKernel\Di\Attribute\Definer;
use SuperKernel\Di\Collector\ReflectionCollector;
use SuperKernel\Di\Contract\ContainerInterface;
use SuperKernel\Di\Contract\DefinerInterface;
use SuperKernel\Di\Contract\DefinitionInterface;
use SuperKernel\Di\Contract\ReflectionCollectorInterface;
use SuperKernel\Di\Definition\ObjectDefinition;

#[Definer]
final class ObjectDefiner implements DefinerInterface
{
	/**
	 * @var ReflectionCollectorInterface|null
	 * @psalm-var ReflectionCollector
	 */
	private ?ReflectionCollectorInterface $reflectionCollector = null {
		get => $this->reflectionCollector ??= $this->container->get(ReflectionCollectorInterface::class);
	}

	public function __construct(private readonly ContainerInterface $container)
	{
	}

	/**
	 * @param string $id
	 *
	 * @return bool
	 */
	public function support(string $id): bool
	{
		try {
			$reflectionClass = $this->reflectionCollector->reflectClass($id);
		}
		catch (ReflectionException) {
			return false;
		}

		return $reflectionClass->isInstantiable();
	}

	/**
	 * @param string $id
	 *
	 * @return DefinitionInterface
	 */
	public function create(string $id): DefinitionInterface
	{
		return new ObjectDefinition($id);
	}
}