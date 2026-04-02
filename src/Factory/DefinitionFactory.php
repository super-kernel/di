<?php
declare(strict_types=1);

namespace SuperKernel\Di\Factory;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use SplPriorityQueue;
use SuperKernel\Attribute\Provider;
use SuperKernel\Contract\AnnotationCollectorInterface;
use SuperKernel\Di\Attribute\Definer;
use SuperKernel\Di\Contract\DefinitionFactoryInterface;
use SuperKernel\Di\Contract\DefinitionInterface;

#[Provider(DefinitionFactoryInterface::class)]
final class DefinitionFactory implements DefinitionFactoryInterface
{
	private AnnotationCollectorInterface $annotationCollector {
		/**
		 * @throws ContainerExceptionInterface
		 * @throws NotFoundExceptionInterface
		 */
		get {
			if (!isset($this->annotationCollector)) {
				$this->annotationCollector = $this->container->get(AnnotationCollectorInterface::class);
				var_dump($this->annotationCollector);
			}
			return $this->annotationCollector;
		}
	}

	private SplPriorityQueue $definers {
		get {
			if (!isset($this->definers)) {
				$splPriorityQueue = new SplPriorityQueue();
				foreach ($this->annotationCollector->getClassesByAttribute(Definer::class) as $annotation) {
					$definer = $annotation->getClass();
					$splPriorityQueue->insert(new $definer($this->container, $this), $annotation->getInstance()->priority);
				}
				$this->definers = $splPriorityQueue;
			}
			return $this->definers;
		}
	}

	private array $definitions = [];

	public function __construct(private readonly ContainerInterface $container)
	{
	}

	public function getDefiners(): SplPriorityQueue
	{
		return clone $this->definers;
	}

	public function getDefinition(string $id): ?DefinitionInterface
	{
		if (isset($this->definitions[$id]) || array_key_exists($id, $this->definitions)) {
			return $this->definitions[$id];
		}



		foreach ($this->getDefiners() as $definer) {
			if ($definer->support($id)) {
				return $this->definitions[$id] ??= $definer->create($id);
			}
		}

		return null;
	}

	public function hasDefinition(string $id): bool
	{
		return $this->getDefinition($id) instanceof DefinitionInterface;
	}
}