<?php
declare(strict_types=1);

namespace SuperKernel\Di;

use Psr\Container\ContainerInterface as PsrContainerInterface;
use SuperKernel\Contract\ContainerInterface;
use SuperKernel\Di\Exception\NotFoundException;
use SuperKernel\Di\Interface\ContainerFactoryInterface;
use SuperKernel\Di\Interface\DefinitionFactoryInterface;
use SuperKernel\Di\Interface\ResolverInterface;

/**
 * @Container
 * @\SuperKernel\Di\Container
 */
final class Container implements ContainerInterface
{
	private array $resolverEntries = [];

	private DefinitionFactoryInterface $definitionFactory;

	private ResolverInterface $resolverDispatcher;

	public function __construct(ContainerFactoryInterface $containerFactory)
	{
		$this->definitionFactory  = $containerFactory->getDefinitionFactory();
		$this->resolverDispatcher = $containerFactory->getResolverDispatcher($this);

		$this->resolverEntries = [
			self::class                  => $this,
			ContainerInterface::class    => $this,
			PsrContainerInterface::class => $this,
		];
	}

	/**
	 * @throws NotFoundException
	 * @internal
	 */
	public function get(string $id): mixed
	{
		if (isset($this->resolverEntries[$id]) || array_key_exists($id, $this->resolverEntries)) {
			return $this->resolverEntries[$id];
		}

		return $this->resolverEntries[$id] = $this->make($id);
	}

	/**
	 * @internal
	 */
	public function has(string $id): bool
	{
		if (isset($this->resolverEntries[$id]) || array_key_exists($id, $this->resolverEntries)) {
			return true;
		}

		return $this->definitionFactory->getDefinition($id)?->isInstantiable() ?? false;
	}

	/**
	 * Containers only manage long-lived objects, and short-lived objects are managed by the caller. Therefore, this
	 * method independently allows the caller to create short-lived objects.
	 *
	 * @param string $id
	 * @param array  $parameters
	 *
	 * @return mixed
	 * @throws NotFoundException
	 */
	public function make(string $id, array $parameters = []): mixed
	{
		$definition = $this->definitionFactory->getDefinition($id);

		if (!$definition) {
			throw new NotFoundException(
				sprintf('Identifier "%s" is not defined.', $id),
			);
		}

		return $this->resolverDispatcher->resolve($definition, $parameters);
	}
}