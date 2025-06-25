<?php
declare(strict_types=1);

namespace SuperKernel\Di\Factory;

use SplPriorityQueue;
use SuperKernel\Contract\ContainerInterface;
use SuperKernel\Di\Annotation\Factory;
use SuperKernel\Di\Contract\DefinitionInterface;
use SuperKernel\Di\Contract\ResolverFactoryInterface;
use SuperKernel\Di\Contract\ResolverInterface;
use SuperKernel\Di\Exception\NotFoundException;
use SuperKernel\Di\Resolver\FactoryResolver;
use SuperKernel\Di\Resolver\ObjectResolver;
use SuperKernel\Di\Resolver\ParameterResolver;

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
		$this->setResolver(new ObjectResolver($this->container));
		$this->setResolver(new ParameterResolver($this->container));
	}

	/**
	 * @param DefinitionInterface $definition
	 *
	 * @return ResolverInterface
	 * @throws NotFoundException
	 */
	public function getResolver(DefinitionInterface $definition): ResolverInterface
	{
		$resolvers = clone $this->resolvers;
		$resolvers->top();

		foreach ($resolvers as $resolver) {
			var_dump(get_class($resolver));
			if (!$resolver->support($definition)) {
				continue;
			}
			return $resolver;
		}

		throw new NotFoundException(
			sprintf('The is no resolver that supports definer "%s".', get_class($definition)),
		);
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