<?php
declare(strict_types=1);

namespace SuperKernel\Di\Definer;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use SuperKernel\Attribute\Factory;
use SuperKernel\Attribute\Provider;
use SuperKernel\Contract\AnnotationCollectorInterface;
use SuperKernel\Contract\AnnotationInterface;
use SuperKernel\Contract\ReflectionCollectorInterface;
use SuperKernel\Di\Attribute\Definer;
use SuperKernel\Di\Contract\DefinerInterface;
use SuperKernel\Di\Contract\DefinitionInterface;
use SuperKernel\Di\Definition\FactoryDefinition;
use SuperKernel\Di\Definition\ObjectDefinition;
use SuperKernel\Di\Exception\Container\ProviderResolutionException;
use Throwable;
use function class_exists;
use function is_null;

#[Definer(1000)]
final class ProviderDefiner implements DefinerInterface
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

	private ReflectionCollectorInterface $reflectionCollector {
		/**
		 * @throws ContainerExceptionInterface
		 * @throws NotFoundExceptionInterface
		 */
		get {
			if (!isset($this->reflectionCollector)) {
				$this->reflectionCollector = $this->container->get(ReflectionCollectorInterface::class);
			}
			return $this->reflectionCollector;
		}
	}

	public function __construct(private readonly ContainerInterface $container)
	{
	}

	public function support(string $id): bool
	{
		$provider = $this->getProvider($id);

		if (is_null($provider)) {
			return false;
		}

		if (!class_exists($provider)) {
			return false;
		}

		try {
			return $this->reflectionCollector->reflectClass($provider)->isInstantiable();
		}
		catch (Throwable) {
			return false;
		}
	}

	public function create(string $id): DefinitionInterface
	{
		$provider = $this->getProvider($id);


		if (null === $provider) {
			throw ProviderResolutionException::noProvider($id);
		}

		try {
			$attributes = $this->reflectionCollector->reflectClass($provider)->getAttributes(Factory::class);

			if (!empty($attributes)) {
				return new FactoryDefinition($id, $provider);
			}
		}
		catch (Throwable) {
			$classAttributes = $this->annotationCollector->getClassAttributes($provider);

			foreach ($classAttributes as $classAttribute) {
				if (!($classAttribute instanceof AnnotationInterface)) {
					continue;
				}

				if (Factory::class === $classAttribute->getAttribute()) {
					return new FactoryDefinition($id, $provider);
				}
			}
		}

		return new ObjectDefinition($id, $provider);
	}

	private function getProvider(string $id): ?string
	{
		$class = null;
		$priority = null;

		$annotations = $this->annotationCollector->getClassesByAttribute(Provider::class);

		foreach ($annotations as $annotation) {
			if (!($annotation instanceof AnnotationInterface)) {
				continue;
			}

			/* @var Provider $provider */
			$provider = $annotation->getInstance();

			if ($id !== $provider->class) {
				continue;
			}

			if (null === $priority) {
				$class = $annotation->getClass();
				$priority = $provider->priority;
			} else {
				if ($priority <= $provider->priority) {
					$class = $annotation->getClass();
					$priority = $provider->priority;
				}
			}
		}

		return $class;
	}
}