<?php
declare(strict_types=1);

namespace SuperKernel\Di\Definer;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use SuperKernel\Contract\ReflectionCollectorInterface;
use SuperKernel\Di\Attribute\Definer;
use SuperKernel\Di\Collector\ProviderCollector;
use SuperKernel\Di\Contract\DefinerInterface;
use SuperKernel\Di\Contract\DefinitionInterface;
use SuperKernel\Di\Definition\ObjectDefinition;
use Throwable;
use function class_exists;

#[Definer]
final class ObjectDefiner implements DefinerInterface
{
	private ProviderCollector $providerCollector {
		/**
		 * @throws ContainerExceptionInterface
		 * @throws NotFoundExceptionInterface
		 */
		get {
			if (!isset($this->providerCollector)) {
				$this->providerCollector = $this->container->get(ProviderCollector::class);
			}
			return $this->providerCollector;
		}
	}

	private ReflectionCollectorInterface $reflectionCollector {
		/**
		 * @throws ContainerExceptionInterface
		 * @throws NotFoundExceptionInterface
		 */
		get {
			if (!isset($this->reflectionCollector)) {
				$this->reflectionCollector = $this->container->get(ReflectionCollectorInterface::class);
			}
			return $this->reflectionCollector;
		}
	}

	public function __construct(private readonly ContainerInterface $container)
	{
	}

	public function support(string $id): bool
	{
		if (!class_exists($id)) {
			return false;
		}

		try {
			return $this->reflectionCollector->reflectClass($id)->isInstantiable();
		}
		catch (Throwable) {
			return false;
		}
	}

	public function create(string $id): DefinitionInterface
	{
		$class = $this->providerCollector->get($id);

		return new ObjectDefinition($id, $class);
	}
}