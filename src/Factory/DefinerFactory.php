<?php
declare(strict_types=1);

namespace SuperKernel\Di\Factory;

use SplPriorityQueue;
use SuperKernel\Di\Annotation\Factory;
use SuperKernel\Di\Contract\DefinerFactoryInterface;
use SuperKernel\Di\Contract\DefinerInterface;
use SuperKernel\Di\Contract\DefinitionInterface;

#[Factory]
final class DefinerFactory implements DefinerFactoryInterface
{
	private static ?DefinerFactory $instance = null;

	private ?SplPriorityQueue $definers = null {
		get => $this->definers ??= new class extends SplPriorityQueue {
			public function compare(mixed $priority1, mixed $priority2): int
			{
				return $priority2 <=> $priority1;
			}
		};
	}

	public function getDefinition(string $id): ?DefinitionInterface
	{
		$definitions = clone $this->definers;
		$definitions->top();

		foreach ($definitions as $definition) {
			if (!$definition->support($id)) {
				continue;
			}

			return $definition->getDefinition($id);
		}

		return null;
	}

	public function setDefiner(DefinerInterface $definer, int $priority = 0): void
	{
		$this->definers->insert($definer, $priority);
	}

	public function __invoke(): DefinerFactory
	{
		return self::$instance ??= $this;
	}
}