<?php
declare(strict_types=1);

namespace SuperKernel\Di;

use SuperKernel\Contract\ContainerInterface;
use SuperKernel\Di\Exception\NotFoundException;
use SuperKernel\Di\Contract\DefinitionFactoryInterface;

/**
 * Containers only manage long-lived objects, and short-lived objects are managed by the caller.
 */
final class Container implements ContainerInterface
{
	private array $resolverEntries = [];

	public function __construct(private ?DefinitionFactoryInterface $definitionFactory = null)
	{
		$this->definitionFactory ??= new DefinitionFactory()();
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

		$definition = $this->definitionFactory->getDefinition($id);

		if (!$definition) {
			throw new NotFoundException(
				sprintf('Identifier "%s" is not defined.', $id),
			);
		}

		return $this->resolverEntries[$id] = $this->definitionFactory->getResolver($definition)->resolve($definition);
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
	 *
	 * @throws NotFoundException
	 *
	 * @deprecated 2.0
	 *
	 */
	public function make(string $id, array $parameters = []): mixed
	{
		$definition = $this->definitionFactory->getDefinition($id);

		if (!$definition) {
			throw new NotFoundException(
				sprintf('Identifier "%s" is not defined.', $id),
			);
		}

		return $this->definitionFactory->getResolver($definition)->resolve($definition, $parameters);
	}
}