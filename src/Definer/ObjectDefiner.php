<?php
declare(strict_types=1);

namespace SuperKernel\Di\Definer;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use SuperKernel\Contract\ReflectorInterface;
use SuperKernel\Di\Attribute\Definer;
use SuperKernel\Di\Contract\DefinerInterface;
use SuperKernel\Di\Contract\DefinitionInterface;
use SuperKernel\Di\Definition\ObjectDefinition;
use Throwable;

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
			$reflectionClass = $this->reflector->reflectClass($id);
		}
		catch (Throwable) {
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