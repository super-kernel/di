<?php
declare(strict_types=1);

namespace SuperKernel\Di\Factory;

use SplPriorityQueue;
use SuperKernel\Contract\ContainerInterface;
use SuperKernel\Di\Annotation\Factory;
use SuperKernel\Di\Container;
use SuperKernel\Di\Contract\DefinitionInterface;
use SuperKernel\Di\Contract\ResolverFactoryInterface;
use SuperKernel\Di\Contract\ResolverInterface;
use SuperKernel\Di\Resolver\FactoryResolver;

#[Factory]
final class ResolverFactory implements ResolverFactoryInterface
{
	private ?SplPriorityQueue $resolvers = null {
		get => $this->resolvers ??= new class extends SplPriorityQueue {
			public function compare(mixed $priority1, mixed $priority2): int
			{
				return $priority2 <=> $priority1;
			}
		};
	}

	public function __construct(private readonly ContainerInterface $container)
	{
		$this->setResolver(new FactoryResolver($this->container));
	}

	public function getResolver(DefinitionInterface $definition): ?ResolverInterface
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

	public function setResolver(ResolverInterface $resolver, int $priority = 0): void
	{
		$this->resolvers->insert($resolver, $priority);
	}

	private static ?ResolverFactory $instance = null;

	public function __invoke(): ResolverFactory
	{
		return self::$instance ??= $this;
	}
}