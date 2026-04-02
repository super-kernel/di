<?php
declare(strict_types=1);

namespace SuperKernel\Di\Factory;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use SuperKernel\Contract\AnnotationCollectorInterface;
use SuperKernel\Di\Attribute\Resolver;
use SuperKernel\Di\Contract\DefinitionInterface;
use SuperKernel\Di\Contract\ResolverFactoryInterface;
use SuperKernel\Di\Contract\ResolverInterface;
use SuperKernel\Di\Exception\NotFoundException;

final class ResolverFactory implements ResolverFactoryInterface
{
	private AnnotationCollectorInterface $annotationCollector {
		/**
		 * @throws ContainerExceptionInterface
		 * @throws NotFoundExceptionInterface
		 */
		get {
			if (!isset($this->annotationCollector)) {
				$this->annotationCollector = $this->container->get(AnnotationCollectorInterface::class);
			}
			return $this->annotationCollector;
		}
	}

	private array $resolvers {
		get {
			if (!isset($this->resolvers)) {
				$resolvers = [];
				foreach ($this->annotationCollector->getClassesByAttribute(Resolver::class) as $annotation) {
					$resolver = $annotation->getClass();
					$resolvers[] = new $resolver($this->container);
				}
				$this->resolvers = $resolvers;
			}
			return $this->resolvers;
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