<?php
declare(strict_types=1);

namespace SuperKernel\Di;

use Psr\Container\ContainerInterface;
use SuperKernel\Attribute\Contract\AttributeCollectorInterface;
use SuperKernel\Contract\ReflectorInterface;
use SuperKernel\Di\Collector\ProviderCollector;
use SuperKernel\Di\Contract\DefinitionFactoryInterface;
use SuperKernel\Di\Contract\ResolverFactoryInterface;
use SuperKernel\Di\Exception\NotFoundException;
use SuperKernel\Di\Factory\DefinitionFactory;
use SuperKernel\Di\Factory\ResolverFactory;
use SuperKernel\Reflection\Provider\ReflectorProvider;

/**
 * Containers only manage long-lived objects, and short-lived objects are managed by the
 * caller {@see https://www.php-fig.org/psr/psr-11/}.
 */
final class Container implements ContainerInterface
{
	private readonly DefinitionFactoryInterface $definitionFactory;

	private readonly ResolverFactoryInterface $resolverFactory;

	private array $resolverEntries;

	final public function __construct(private readonly AttributeCollectorInterface $attributeCollector)
	{
		$this->resolverFactory = new ResolverFactory($this);
		$this->definitionFactory = new DefinitionFactory($this);

		$this->resolverEntries = [
			self::class                        => $this,
			ProviderCollector::class           => new ProviderCollector($this->attributeCollector),
			ContainerInterface::class          => $this,
			ReflectorInterface::class          => new ReflectorProvider()(),
			ResolverFactoryInterface::class    => $this->resolverFactory,
			DefinitionFactoryInterface::class  => $this->definitionFactory,
			AttributeCollectorInterface::class => $this->attributeCollector,
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
		if (!$definition) {
			throw new NotFoundException(
				sprintf('Identifier "%s" is not defined.', $id));
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