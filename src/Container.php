<?php
declare(strict_types=1);

namespace SuperKernel\Di;

use SuperKernel\Di\Contract\ContainerInterface;
use SuperKernel\Di\Contract\DefinerFactoryInterface;
use SuperKernel\Di\Contract\ResolverFactoryInterface;
use SuperKernel\Di\Exception\NotFoundException;
use SuperKernel\Di\Factory\DefinerFactory;
use SuperKernel\Di\Factory\ResolverFactory;

/**
 * Containers only manage long-lived objects, and short-lived objects are managed by the caller.
 */
final class Container implements ContainerInterface
{
	private array $resolverEntries = [];

	private ResolverFactoryInterface $resolverFactory;

	public function __construct(private ?DefinerFactoryInterface $definitionSource = null)
	{
		$this->definitionSource ??= new DefinerFactory();
		$this->resolverFactory  = new ResolverFactory($this);

		$this->resolverEntries = [
			self::class                     => $this,
			ResolverFactoryInterface::class => $this->resolverFactory,
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

		$definition = $this->definitionSource->getDefinition($id);

		if (!$definition) {
			throw new NotFoundException(
				sprintf('Identifier "%s" is not defined.', $id),
			);
		}

		return $this->resolverEntries[$id] = $this->resolverFactory->getResolver($definition)->resolve($definition);
	}

	/**
	 * @internal
	 */
	public function has(string $id): bool
	{
		if (isset($this->resolverEntries[$id]) || array_key_exists($id, $this->resolverEntries)) {
			return true;
		}

		return $this->definitionSource->getDefinition($id)?->isInstantiable() ?? false;
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
		$definition = $this->definitionSource->getDefinition($id);

		if (!$definition) {
			throw new NotFoundException(
				sprintf('Identifier "%s" is not defined.', $id),
			);
		}

		return $this->resolverFactory->getResolver($definition)->resolve($definition, $parameters);
	}
}