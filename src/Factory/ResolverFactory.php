<?php
declare(strict_types=1);

namespace SuperKernel\Di\Factory;

use SplPriorityQueue;
use SuperKernel\Di\Annotation\Factory;
use SuperKernel\Di\Contract\DefinitionInterface;
use SuperKernel\Di\Contract\ResolverInterface;

#[Factory]
final class ResolverFactory
{
	private ?SplPriorityQueue $resolvers = null {
		get => $this->resolvers ??= new class extends SplPriorityQueue {
			public function compare(mixed $priority1, mixed $priority2): int
			{
				return $priority2 <=> $priority1;
			}
		};
	}

	public function getResolver(DefinitionInterface $definition): ?DefinitionInterface
	{
		$resolvers = clone $this->resolvers;
		$resolvers->top();

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

	private static ?ResolverFactory $instance = null;

	public function __invoke(): ResolverFactory
	{
		return self::$instance ??= $this;
	}
}