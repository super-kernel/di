<?php
declare(strict_types=1);

namespace SuperKernel\Di;

use Psr\Container\ContainerInterface as PsrContainerInterface;
use SuperKernel\Contract\ReflectionManagerInterface;
use SuperKernel\Di\Collector\ReflectionManager;
use SuperKernel\Di\Contract\ContainerInterface;
use SuperKernel\Di\Contract\DefinitionFactoryInterface;
use SuperKernel\Di\Contract\ResolverFactoryInterface;
use SuperKernel\Di\Exception\NotFoundException;
use SuperKernel\Di\Factory\DefinitionFactory;
use SuperKernel\Di\Factory\ResolverFactory;

/**
 * Containers only manage long-lived objects, and short-lived objects are managed by the
 * caller {@see https://www.php-fig.org/psr/psr-11/}.
 */
final class Container implements ContainerInterface
{
	private readonly DefinitionFactoryInterface $definitionFactory;

	private readonly ResolverFactoryInterface $resolverFactory;

	private array $resolverEntries;

	final public function __construct(array $attributes)
	{
		$reflectionManager       = new ReflectionManager()($attributes);
		$this->resolverFactory   = new ResolverFactory($this);
		$this->definitionFactory = new DefinitionFactory();

		$this->resolverEntries = [
			self::class                       => $this,
			ReflectionManager::class          => $reflectionManager,
			ContainerInterface::class         => $this,
			PsrContainerInterface::class      => $this,
			ResolverFactoryInterface::class   => $this->resolverFactory,
			DefinitionFactoryInterface::class => $this->definitionFactory,
			ReflectionManagerInterface::class => $reflectionManager,
		];
	}

	/**
	 * @throws NotFoundException
	 * @internal
	 */
	final public function get(string $id): mixed
	{
		if (isset($this->resolverEntries[$id]) || array_key_exists($id, $this->resolverEntries)) {
			return $this->resolverEntries[$id];
		}

		return $this->resolverEntries[$id] ??= $this->make($id);
	}

	/**
	 * @internal
	 */
	final public function has(string $id): bool
	{
		if (isset($this->resolverEntries[$id]) || array_key_exists($id, $this->resolverEntries)) {
			return true;
		}

		if ($this->definitionFactory->hasDefinition($id)) {
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
	 */
	final public function make(string $id, array $parameters = []): mixed
	{
		$definition = $this->definitionFactory->getDefinition($id);

		if (!$definition) {
			throw new NotFoundException(
				sprintf('Identifier "%s" is not defined.', $id));
		}

		return $this->resolverFactory->getResolver($definition)->resolve($definition, $parameters);
	}
}