<?php
declare(strict_types=1);

namespace SuperKernel\Di;

use SplPriorityQueue;
use SuperKernel\Di\Annotation\Factory;
use SuperKernel\Di\Contract\DefinitionFactoryInterface;
use SuperKernel\Di\Contract\DefinitionInterface;
use SuperKernel\Di\Contract\ResolverInterface;

#[Factory]
final class DefinitionFactory implements DefinitionFactoryInterface
{
	private static ?DefinitionFactoryInterface $instance = null;

	private readonly ?SplPriorityQueue $definitions;

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
}