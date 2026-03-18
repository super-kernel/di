<?php
declare(strict_types=1);

namespace SuperKernel\Di;

use SuperKernel\Contract\AttributeMetadataCollectorInterface;
use SuperKernel\Contract\ContainerInterface;
use SuperKernel\Contract\ReflectionCollectorInterface;
use SuperKernel\Di\Collector\ProviderCollector;
use SuperKernel\Di\Collector\ReflectionCollector;
use SuperKernel\Di\Contract\DefinitionFactoryInterface;
use SuperKernel\Di\Contract\ResolverFactoryInterface;
use SuperKernel\Di\Exception\NotFoundException;
use SuperKernel\Di\Provider\DefinitionFactoryProvider;
use SuperKernel\Di\Provider\ResolverFactoryProvider;
use function is_null;

/**
 * Containers only manage long-lived objects, and short-lived objects are managed by the
 * caller {@see https://www.php-fig.org/psr/psr-11/}.
 */
final class Container implements ContainerInterface
{
	private readonly DefinitionFactoryInterface $definitionFactory;

	private readonly ResolverFactoryInterface $resolverFactory;

	private array $resolverEntries;

	final public function __construct(AttributeMetadataCollectorInterface $attributeMetadataCollector)
	{
		$this->resolverFactory = new ResolverFactoryProvider()($this);
		$this->definitionFactory = new DefinitionFactoryProvider()($this);
		$this->resolverEntries = [
			ProviderCollector::class                   => new ProviderCollector($attributeMetadataCollector),
			ResolverFactoryInterface::class            => $this->resolverFactory,
			DefinitionFactoryInterface::class          => $this->definitionFactory,
			ReflectionCollectorInterface::class        => new ReflectionCollector(),
			AttributeMetadataCollectorInterface::class => $attributeMetadataCollector,
		];
	}

	/**
	 * @inheritDoc
	 */
	final public function get(string $id): mixed
	{
		if (isset($this->resolverEntries[$id]) || array_key_exists($id, $this->resolverEntries)) {
			return $this->resolverEntries[$id];
		}

		$definition = $this->definitionFactory->getDefinition($id);
		if (is_null($definition)) {
			throw new NotFoundException(sprintf('Identifier "%s" is not defined.', $id));
		}

		return $this->resolverEntries[$id] ??= $this->resolverFactory->getResolver($definition)->resolve($definition);
	}

	/**
	 * @inheritDoc
	 */
	final public function has(string $id): bool
	{
		if (isset($this->resolverEntries[$id]) || array_key_exists($id, $this->resolverEntries)) {
			return true;
		}

		return $this->definitionFactory->hasDefinition($id);
	}
}