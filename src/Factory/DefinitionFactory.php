<?php
declare(strict_types=1);

namespace SuperKernel\Di\Factory;

use Psr\Container\ContainerInterface;
use SplPriorityQueue;
use SuperKernel\Attribute\Provider;
use SuperKernel\Contract\AttributeCollectorInterface;
use SuperKernel\Contract\ReflectionCollectorInterface;
use SuperKernel\Di\Annotation\Definer;
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

				foreach ($this->attributeCollector->getAttributes(Definer::class) as $definer => $attributes) {
					/* @var Definer $attribute */
					foreach ($attributes as $attribute) {
						$this->definers->insert(new $definer($this->container), $attribute->priority);
					}
				}
			}

			return $this->definers;
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