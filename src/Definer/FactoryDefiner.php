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
use SuperKernel\Di\Contract\DefinitionFactoryInterface;
use SuperKernel\Di\Contract\DefinitionInterface;
use SuperKernel\Di\Definition\FactoryDefinition;
use SuperKernel\Di\Exception\Container\FactoryResolutionException;
use Throwable;
use function method_exists;

#[Definer(200)]
final class FactoryDefiner implements DefinerInterface
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
		try {
			$this->create($id);
		}
		catch (Throwable) {
			return false;
		}

		return true;
	}

	/**
	 * @param string $id
	 *
	 * @return DefinitionInterface
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 */
	public function create(string $id): DefinitionInterface
	{
		$definers = $this->container->get(DefinitionFactoryInterface::class);

		/* @var DefinerInterface $definer */
		foreach ($definers->getDefiners() as $definer) {
			if ($definer instanceof self) {
				continue;
			}
			if ($definer->support($id)) {
				/* @var DefinitionInterface $definition */
				$definition = $definer->create($id);
				if (method_exists($definition->getClassName(), '__invoke')) {
					return new FactoryDefinition($id, $definition->getClassName());
				}
			}
		}

		throw FactoryResolutionException::noInstantiationDefiner($id);
	}
}