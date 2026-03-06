<?php
declare(strict_types=1);

namespace SuperKernel\Di\Factory;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use SplPriorityQueue;
use SuperKernel\Annotation\Provider;
use SuperKernel\Attribute\Contract\AttributeCollectorInterface;
use SuperKernel\Di\Attribute\Definer;
use SuperKernel\Di\Contract\DefinerInterface;
use SuperKernel\Di\Contract\DefinitionFactoryInterface;
use SuperKernel\Di\Contract\DefinitionInterface;

#[Provider(DefinitionFactoryInterface::class)]
final class DefinitionFactory implements DefinitionFactoryInterface
{
	private array $definitions = [];

	private SplPriorityQueue $definers {
		get {
			if (!isset($this->definers)) {
				$this->definers = new SplPriorityQueue;
				foreach ($this->attributeCollector->getAttributes(Definer::class) as $attribute) {
					$definer = $attribute->getClass();
					$this->definers->insert(new $definer($this->container), $attribute->getInstance()->priority);
				}
			}
			return $this->definers;
		}
	}

	private ?AttributeCollectorInterface $attributeCollector = null {
		/**
		 * @throws ContainerExceptionInterface
		 * @throws NotFoundExceptionInterface
		 */
		get => $this->attributeCollector ??= $this->container->get(AttributeCollectorInterface::class);
	}

	public function __construct(private readonly ContainerInterface $container)
	{
	}

	public function getDefinition(string $id): ?DefinitionInterface
	{
		if (isset($this->definitions[$id]) || array_key_exists($id, $this->definitions)) {
			return $this->definitions[$id];
		}

		$definers = clone $this->definers;

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
		return !(null === $this->getDefinition($id));
	}
}