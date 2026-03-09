<?php
declare(strict_types=1);

namespace SuperKernel\Di\Factory;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use SuperKernel\Annotation\Provider;
use SuperKernel\Attribute\Contract\AttributeCollectorInterface;
use SuperKernel\Di\Attribute\Resolver;
use SuperKernel\Di\Contract\DefinitionInterface;
use SuperKernel\Di\Contract\ResolverFactoryInterface;
use SuperKernel\Di\Contract\ResolverInterface;
use SuperKernel\Di\Exception\NotFoundException;

#[Provider(ResolverFactoryInterface::class)]
final class ResolverFactory implements ResolverFactoryInterface
{
	private array $resolvers {
		get {
			if (!isset($this->resolvers)) {
				$resolvers = [];
				foreach ($this->attributeCollector->getClassesByAttribute(Resolver::class) as $attribute) {
					$resolver = $attribute->getClass();
					$resolvers[] = new $resolver($this->container);
				}

				$this->resolvers = $resolvers;
			}
			return $this->resolvers;
		}
	}

	private AttributeCollectorInterface $attributeCollector {
		/**
		 * @throws ContainerExceptionInterface
		 * @throws NotFoundExceptionInterface
		 */
		get {
			if (!isset($this->attributeCollector)) {
				$this->attributeCollector = $this->container->get(AttributeCollectorInterface::class);
			}
			return $this->attributeCollector;
		}
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
		foreach ($this->resolvers as $resolver) {
			if ($resolver->support($definition)) {
				return $resolver;
			}
		}

		throw new NotFoundException("The is no resolver that supports definer $definition");
	}
}