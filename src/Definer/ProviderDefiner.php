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
use SuperKernel\Di\Definition\ProviderDefinition;
use SuperKernel\Di\Exception\Container\ProviderResolutionException;
use function class_exists;
use function is_null;

#[Definer(100)]
final class ProviderDefiner implements DefinerInterface
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

	public function __construct(private readonly ContainerInterface $container)
	{
	}

	public function support(string $id): bool
	{
		$provider = $this->providerCollector->get($id);

		if (is_null($provider)) {
			return false;
		}

		if (!class_exists($provider)) {
			return false;
		}

		return $this->reflector->reflectClass($provider)->isInstantiable();
	}

	public function create(string $id): DefinitionInterface
	{
		$provider = $this->providerCollector->get($id);

		if (null === $provider) {
			throw ProviderResolutionException::noProvider($id);
		}

		return new ProviderDefinition($id, $provider);
	}
}