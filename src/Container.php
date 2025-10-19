<?php
declare(strict_types=1);

namespace SuperKernel\Di;

use Psr\Container\ContainerInterface as PsrContainerInterface;
use SuperKernel\Contract\AttributeCollectorInterface;
use SuperKernel\Contract\ReflectionCollectorInterface;
use SuperKernel\Di\Collector\AttributeCollector;
use SuperKernel\Di\Collector\ReflectionCollector;
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
		$attributeCollector      = new AttributeCollector($this, $attributes);
		$reflectionCollector     = new ReflectionCollector();
		$this->resolverFactory   = new ResolverFactory($this);
		$this->definitionFactory = new DefinitionFactory($this);

		$this->resolverEntries = [
			self::class                         => $this,
			ReflectionCollector::class          => $reflectionCollector,
			AttributeCollector::class           => $attributeCollector,
			ContainerInterface::class           => $this,
			PsrContainerInterface::class        => $this,
			ResolverFactoryInterface::class     => $this->resolverFactory,
			DefinitionFactoryInterface::class   => $this->definitionFactory,
			ReflectionCollectorInterface::class => $reflectionCollector,
			AttributeCollectorInterface::class  => $attributeCollector,
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

		$definition = $this->definitionFactory->getDefinition($id);

		if (!$definition) {
			throw new NotFoundException(
				sprintf('Identifier "%s" is not defined.', $id));
		}

		return $this->resolverEntries[$id] ??= $this->resolverFactory->getResolver($definition)->resolve($definition);
	}

	/**
	 * @internal
	 */
	final public function has(string $id): bool
	{
		if (isset($this->resolverEntries[$id]) || array_key_exists($id, $this->resolverEntries)) {
			return true;
		}

		return $this->definitionFactory->hasDefinition($id);
	}
}