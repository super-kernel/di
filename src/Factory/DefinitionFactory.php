<?php
declare(strict_types=1);

namespace SuperKernel\Di\Factory;

use SplPriorityQueue;
use SuperKernel\Di\Annotation\Definer;
use SuperKernel\Di\Annotation\Resolver;
use SuperKernel\Di\Collector\ReflectionManager;
use SuperKernel\Di\Contract\DefinerInterface;
use SuperKernel\Di\Contract\DefinitionFactoryInterface;
use SuperKernel\Di\Contract\DefinitionInterface;

final class DefinitionFactory implements DefinitionFactoryInterface
{
	/** @noinspection PhpGetterAndSetterCanBeReplacedWithPropertyHooksInspection */
	private array $definitions = [];

	private ?SplPriorityQueue $definers = null {
		get => $this->definers ??= new class extends SplPriorityQueue {
		};
	}

	public function __construct()
	{
		$definers = ReflectionManager::getAttributes(Definer::class);

		foreach ($definers as $definer) {
			// TODO:The feature cannot be duplicated because it does not contain the `Attribute:: IS-REPEATABLE` flag.
			$attributes = ReflectionManager::getClassAnnotations($definer, Definer::class);

			/* @var Resolver $attribute */
			$attribute = $attributes[0]->newInstance();
			$priority  = $attribute->priority;

			/* @var DefinitionInterface $definer */
			$this->definers->insert(new $definer, $priority);
		}
	}

	public function getDefinition(string $id): ?DefinitionInterface
	{
		if (isset($this->definitions[$id]) || array_key_exists($id, $this->definitions)) {
			return $this->definitions[$id];
		}

		$definers = clone $this->definers;

		$definers->top();

		/* @var DefinerInterface $definer */
		foreach ($definers as $definer) {
			if (!$definer->support($id)) {
				continue;
			}
			return $this->definitions[$id] ??= $definer->create($id);
		}

		return null;
	}

	public function getDefinitions(): array
	{
		return $this->definitions;
	}

	public function hasDefinition(string $id): bool
	{
		return $this->getDefinition($id) !== null;
	}
}