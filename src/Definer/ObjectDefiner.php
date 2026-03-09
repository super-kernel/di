<?php
declare(strict_types=1);

namespace SuperKernel\Di\Definer;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use SuperKernel\Contract\ReflectorInterface;
use SuperKernel\Di\Attribute\Definer;
use SuperKernel\Di\Collector\ProviderCollector;
use SuperKernel\Di\Contract\DefinerInterface;
use SuperKernel\Di\Contract\DefinitionInterface;
use SuperKernel\Di\Definition\ObjectDefinition;
use function class_exists;

#[Definer]
final class ObjectDefiner implements DefinerInterface
{
	private ReflectorInterface $reflector {
		/**
		 * @throws ContainerExceptionInterface
		 * @throws NotFoundExceptionInterface
		 */
		get {
			if (!isset($this->reflector)) {
				$this->reflector = $this->container->get(ReflectorInterface::class);
			}
			return $this->reflector;
		}
	}

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

	public function __construct(private readonly ContainerInterface $container)
	{
	}

	public function support(string $id): bool
	{
		if (!class_exists($id)) {
			return false;
		}

		return $this->reflector->reflectClass($id)->isInstantiable();
	}

	public function create(string $id): DefinitionInterface
	{
		$class = $this->providerCollector->get($id);

		return new ObjectDefinition($id, $class);
	}
}