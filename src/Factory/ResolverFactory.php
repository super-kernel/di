<?php
declare(strict_types=1);

namespace SuperKernel\Di\Factory;

use SplPriorityQueue;
use SuperKernel\Attribute\Factory;
use SuperKernel\Di\Annotation\Resolver;
use SuperKernel\Di\Collector\ReflectionManager;
use SuperKernel\Di\Container;
use SuperKernel\Di\Contract\DefinitionInterface;
use SuperKernel\Di\Contract\ResolverFactoryInterface;
use SuperKernel\Di\Contract\ResolverInterface;
use SuperKernel\Di\Exception\NotFoundException;

#[
	Factory,
]
final class ResolverFactory implements ResolverFactoryInterface
{
	private static ?ResolverFactory $resolverFactory = null;

	private ?SplPriorityQueue $resolvers = null {
		get => $this->resolvers ??= new class extends SplPriorityQueue {
		};
	}

	public function __construct(Container $container)
	{
		$resolvers = ReflectionManager::getAttributes(Resolver::class);

		foreach ($resolvers as $resolver) {
			// TODO:The feature cannot be duplicated because it does not contain the `Attribute:: IS-REPEATABLE` flag.
			$attributes = ReflectionManager::getClassAnnotations($resolver, Resolver::class);

			/* @var Resolver $attribute */
			$attribute = $attributes[0]->newInstance();
			$priority  = $attribute->priority;

			/* @var ResolverInterface $resolver */
			$this->resolvers->insert(new $resolver($container), $priority);
		}
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
			if (!$resolver->support($definition)) {
				continue;
			}
			return $resolver;
		}

		throw new NotFoundException(
			sprintf('The is no resolver that supports definer "%s".', get_class($definition)),
		);
	}

	public function __invoke(): ResolverFactory
	{
		return self::$resolverFactory ??= $this;
	}
}