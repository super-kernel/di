<?php
declare(strict_types=1);

namespace SuperKernel\Di\Factory;

use Psr\Container\ContainerInterface;
use SplPriorityQueue;
use SuperKernel\Contract\AttributeCollectorInterface;
use SuperKernel\Contract\ReflectionCollectorInterface;
use SuperKernel\Di\Annotation\Resolver;
use SuperKernel\Di\Contract\DefinitionInterface;
use SuperKernel\Di\Contract\ResolverFactoryInterface;
use SuperKernel\Di\Contract\ResolverInterface;
use SuperKernel\Di\Exception\NotFoundException;

final class ResolverFactory implements ResolverFactoryInterface
{
	private SplPriorityQueue $resolvers {
		get {
			if (!isset($this->resolvers)) {
				$this->resolvers = new SplPriorityQueue;

				/* @var array<Resolver> $attributes */
				foreach ($this->attributeCollector->getAttributes(Resolver::class) as $resolver => $attributes) {
					foreach ($attributes as $attribute) {
						$this->resolvers->insert(new $resolver($this->container), $attribute->priority);
					}
				}
			}

			return $this->resolvers;
		}
	}

	private ?AttributeCollectorInterface $attributeCollector = null {
		get => $this->attributeCollector ??= $this->container->get(AttributeCollectorInterface::class);
	}

	private ?ReflectionCollectorInterface $reflectionManager = null {
		get => $this->reflectionManager ??= $this->container->get(ReflectionCollectorInterface::class);
	}

	public function __construct(private readonly ContainerInterface $container)
	{
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

		while (!$resolvers->isEmpty()) {
			$resolver = $resolvers->extract();

			if ($resolver->support($definition)) {
				return $resolver;
			}
		}

		throw new NotFoundException("The is no resolver that supports definer $definition");
	}
}