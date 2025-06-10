<?php
declare (strict_types=1);

namespace SuperKernel\Di\Abstract;

use SplPriorityQueue;
use SuperKernel\Di\Contract\ResolverInterface;
use SuperKernel\Di\Contract\DefinitionFactoryInterface;
use SuperKernel\Di\Contract\DefinitionInterface;

/**
 * @AbstractDefinitionFactory
 * @\SuperKernel\Di\AbstractDefinitionFactory
 */
abstract class AbstractDefinitionFactory implements DefinitionFactoryInterface
{
	private static ?DefinitionFactoryInterface $instance = null;

	private readonly SplPriorityQueue $definitions;

	private readonly SplPriorityQueue $resolvers;

	public function getDefinition(string $id): ?DefinitionInterface
	{
		$definitions = clone $this->definitions;
		$definitions->top();

		foreach ($definitions as $definition) {
			$definer = $definition->getDefinition($id);
			if ($definer instanceof DefinitionInterface) {
				return $definer;
			}
		}

		return null;
	}

	public function getResolver(DefinitionInterface $definition): ?ResolverInterface
	{
		$resolvers = clone $this->resolvers;

		foreach ($resolvers as $resolver) {
			if ($resolver->supports($definition)) {
				return $resolver->get($definition);
			}
		}

		return null;
	}

	public function setResolver(ResolverInterface $resolver, int $priority): void
	{
		$this->resolvers->insert($resolver, $priority);
	}

	public function setDefinition(DefinitionInterface $definition, int $priority): void
	{
		$this->resolvers->insert($definition, $priority);
	}

	public function __invoke(): DefinitionFactoryInterface
	{
		return self::$instance ??= (function () {
			$this->definitions = new class extends SplPriorityQueue {
				public function compare(mixed $priority1, mixed $priority2): int
				{
					return $priority2 <=> $priority1;
				}
			};

			$this->resolvers = new class extends SplPriorityQueue {
				public function compare(mixed $priority1, mixed $priority2): int
				{
					return $priority2 <=> $priority1;
				}
			};

			return $this;
		})();
	}

//	private array $definitions = [];
//
//	public function __construct(array $dependencies = [])
//	{
//		$this->normalizeDefinition($dependencies);
//	}
//
//	/**
//	 * @param string $id
//	 *
//	 * @return DefinitionInterface|null
//	 */
//	public function getDefinition(string $id): ?DefinitionInterface
//	{
//		return $this->definitions[$id] ??= $this->autowire($id);
//	}
//
//	private function normalizeDefinition(array $dependencies): void
//	{
//		foreach ($dependencies as $id => $definition) {
//			if (is_string($id) && is_string($definition) && class_exists($definition)) {
//				$this->definitions[$id] = $this->autowire($id, new ObjectDefinition($id, $definition));
//				continue;
//			}
//			if ('factories' === $id && is_array($definition)) {
//				foreach ($definition as $name => $definitionFactory) {
//					if (is_string($name) && is_string($definitionFactory) && class_exists($definitionFactory)) {
//						var_dump(
//							'Factory',
//							$name, $definitionFactory,
//						);
//						$this->definitions[$name] = new FactoryDefinition($name, $definitionFactory);
//					}
//				}
//				continue;
//			}
//
//			$this->definitions[$id] = null;
//		}
//	}
//
//	private function autowire(string $name, ?DefinitionInterface $definition = null): ?DefinitionInterface
//	{
//		$className = $definition?->getName() ?? $name;
//		if (!class_exists($className) && !interface_exists($className)) {
//			return $definition;
//		}
//
//		return $definition ?: new ObjectDefinition($name);
//	}
}