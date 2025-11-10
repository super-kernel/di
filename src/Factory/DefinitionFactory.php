<?php
declare(strict_types=1);

namespace SuperKernel\Di\Factory;

use Psr\Container\ContainerInterface;
use SplPriorityQueue;
use SuperKernel\Di\Attribute\Definer;
use SuperKernel\Di\Attribute\Provider;
use SuperKernel\Di\Contract\AttributeCollectorInterface;
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
					$definer = $attribute->class;
					$this->definers->insert(new $definer($this->container), $attribute->attribute->priority);
				}
			}
			return $this->definers;
		}
	}

	private ?AttributeCollectorInterface $attributeCollector = null {
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
		return $this->getDefinition($id) !== null;
	}
}