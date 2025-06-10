<?php
declare(strict_types=1);

namespace SuperKernel\Di\Factory;

use SplPriorityQueue;
use SuperKernel\Di\Annotation\Factory;
use SuperKernel\Di\Contract\DefinitionInterface;

#[Factory]
final class DefinerFactory
{
	private ?SplPriorityQueue $definitions = null {
		get => $this->definitions ??= new class extends SplPriorityQueue {
			public function compare(mixed $priority1, mixed $priority2): int
			{
				return $priority2 <=> $priority1;
			}
		};
	}

	public function getDefinition(string $id): ?DefinitionInterface
	{
		$definitions = clone $this->definitions;
		$definitions->top();

		foreach ($definitions as $definition) {
			$definer = $definition->getDefinition($id);
			if ($definer instanceof DefinitionInterface) {
				return $definer;
			}
		}

		return null;
	}

	public function setDefinition(DefinitionInterface $definition, int $priority): void
	{
		$this->definitions->insert($definition, $priority);
	}

	private static ?DefinerFactory $instance = null;

	public function __invoke(): DefinerFactory
	{
		return self::$instance ??= $this;
	}
}