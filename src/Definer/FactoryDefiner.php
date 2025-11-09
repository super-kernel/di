<?php
declare(strict_types=1);

namespace SuperKernel\Di\Definer;

use Psr\Container\ContainerInterface;
use ReflectionException;
use SuperKernel\Di\Attribute\Definer;
use SuperKernel\Di\Attribute\Factory;
use SuperKernel\Di\Collector\ReflectionCollector;
use SuperKernel\Di\Contract\DefinerInterface;
use SuperKernel\Di\Contract\DefinitionInterface;
use SuperKernel\Di\Contract\ReflectionCollectorInterface;
use SuperKernel\Di\Definition\FactoryDefinition;

#[Definer(2)]
final class FactoryDefiner implements DefinerInterface
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

	public function support(string $id): bool
	{
		try {
			$reflectionClass = $this->reflectionCollector->reflectClass($id);
		}
		catch (ReflectionException) {
			return false;
		}

		if (false === $reflectionClass->isInstantiable()) {
			return false;
		}

		if (true === empty($reflectionClass->getAttributes(Factory::class))) {
			return false;
		}

		return $reflectionClass->hasMethod('__invoke');
	}

	/**
	 * @param string $id
	 *
	 * @return DefinitionInterface
	 */
	public function create(string $id): DefinitionInterface
	{
		return new FactoryDefinition($id);
	}
}