<?php
declare(strict_types=1);

namespace SuperKernel\Di\Factory;

use SplPriorityQueue;
use SuperKernel\Di\Annotation\Factory;
use SuperKernel\Di\Collector\ReflectionManager;
use SuperKernel\Di\Contract\ContainerInterface;
use SuperKernel\Di\Contract\DefinitionInterface;
use SuperKernel\Di\Contract\ResolverFactoryInterface;
use SuperKernel\Di\Contract\ResolverInterface;
use SuperKernel\Di\Exception\Exception;
use SuperKernel\Di\Exception\NotFoundException;
use SuperKernel\Di\Resolver\FactoryResolver;
use SuperKernel\Di\Resolver\ObjectResolver;
use SuperKernel\Di\Resolver\ParameterResolver;

#[Factory]
final class ResolverFactory implements ResolverFactoryInterface
{
	private static ?ResolverFactory $resolverFactory = null;

	private ?SplPriorityQueue $resolverClasses = null {
		get => $this->resolverClasses ??= new class extends SplPriorityQueue {
			public function compare(mixed $priority1, mixed $priority2): int
			{
				return $priority2 <=> $priority1;
			}
		};
	}

	private ?SplPriorityQueue $resolvers = null {
		get => $this->resolvers ??= new class extends SplPriorityQueue {
			public function compare(mixed $priority1, mixed $priority2): int
			{
				return $priority2 <=> $priority1;
			}
		};
	}

	private ContainerInterface $container;

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
			if (!$resolver->support($definition)) {
				continue;
			}
			return $resolver;
		}

		throw new NotFoundException(
			sprintf('The is no resolver that supports definer "%s".', get_class($definition)),
		);
	}

	public function setResolver(string $resolver, int $priority = 0): void
	{
		$this->resolverClasses->insert($resolver, $priority);
	}

	public function setContainer(ContainerInterface $container): void
	{
		$this->container = $container;

		$classes = $this->resolverClasses;
		$classes->top();
		$classes->setExtractFlags(SplPriorityQueue::EXTR_BOTH);

		while ($classes->valid()) {
			[
				'data'     => $resolver,
				'priority' => $priority,
			] = $classes->current();

			if (!ReflectionManager::reflectClass($resolver)->implementsInterface(ResolverInterface::class)) {
				throw new Exception(
					sprintf(
						'Resolver must implement ResolverInterface: %s',
						get_class($resolver),
					),
				);
			}

			$this->resolvers->insert(new $resolver($this->container), $priority);

			$classes->next();
		}
	}

	public function __invoke(): ResolverFactory
	{
		return self::$resolverFactory ??= $this;
	}
}