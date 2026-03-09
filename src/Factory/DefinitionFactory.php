<?php
declare(strict_types=1);

namespace SuperKernel\Di\Factory;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use SplPriorityQueue;
use SuperKernel\Annotation\Provider;
use SuperKernel\Attribute\Contract\AttributeCollectorInterface;
use SuperKernel\Attribute\Metadata\ClassAttribute;
use SuperKernel\Di\Attribute\Definer;
use SuperKernel\Di\Contract\DefinerInterface;
use SuperKernel\Di\Contract\DefinitionFactoryInterface;
use SuperKernel\Di\Contract\DefinitionInterface;
use function is_null;

#[Provider(DefinitionFactoryInterface::class)]
final class DefinitionFactory implements DefinitionFactoryInterface
{
	private array $definitions = [];

	private SplPriorityQueue $definers {
		get {
			if (!isset($this->definers)) {
				$splPriorityQueue = new SplPriorityQueue;
				foreach ($this->attributeCollector->getClassesByAttribute(Definer::class) as $attribute) {
					$definer = $attribute->getClass();
					$splPriorityQueue->insert(new $definer($this->container), $attribute->getInstance()->priority);
				}

				$this->definers = $splPriorityQueue;
			}

			return $this->definers;
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

	public function getDefiners(): SplPriorityQueue
	{
		return clone $this->definers;
	}

	public function getDefinition(string $id): ?DefinitionInterface
	{
		if (isset($this->definitions[$id]) || array_key_exists($id, $this->definitions)) {
			return $this->definitions[$id];
		}

		$definers = $this->getDefiners();

		while (!$definers->isEmpty()) {
			/* @var DefinerInterface $definer */
			$definer = $definers->extract();

			if ($definer->support($id)) {
				return $this->definitions[$id] ??= $definer->create($id);
			}
		}

		return null;
	}

	public function hasDefinition(string $id): bool
	{
		return !is_null($this->getDefinition($id));
	}
}