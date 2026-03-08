<?php
declare(strict_types=1);

namespace SuperKernel\Di\Collector;

use SuperKernel\Annotation\Provider;
use SuperKernel\Attribute\AttributeCollector;

final readonly class ProviderCollector
{
	private array $containers;

	public function __construct(AttributeCollector $attributeCollector)
	{
		$collected = [];
		foreach ($attributeCollector->getAttributes() as $attribute) {
			$instance = $attribute->getInstance();
			if ($instance instanceof Provider) {
				$id = $attribute->getClass();
				$priority = $collected[$id]['priority'] ?? -1;
				if ($instance->priority > $priority) {
					$collected[$id] = [
						'class'    => $instance->class,
						'priority' => $instance->priority,
					];
				}
			}
		}

		$this->containers = array_map(fn($item) => $item['class'], $collected);
	}

	public function has(string $id): bool
	{
		return isset($this->containers[$id]);
	}

	public function get(string $id): ?string
	{
		return $this->containers[$id] ?? null;
	}
}