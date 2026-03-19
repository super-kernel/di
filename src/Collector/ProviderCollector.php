<?php
declare(strict_types=1);

namespace SuperKernel\Di\Collector;

use SuperKernel\Annotation\Provider;
use SuperKernel\Contract\AttributeMetadataCollectorInterface;
use function array_map;

final readonly class ProviderCollector
{
	private array $containers;

	public function __construct(AttributeMetadataCollectorInterface $attributeCollector)
	{
		$containers = [];
		foreach ($attributeCollector->getClassesByAttribute(Provider::class) as $attribute) {
			$attributeInstance = $attribute->getInstance();
			if ($attributeInstance instanceof Provider) {
				$instance = $attribute->getInstance();
				$id = $instance->class;
				$priority = $containers[$id]['priority'] ?? -1;
				if ($instance->priority >= $priority) {
					$containers[$id] = [
						'class'    => $attribute->getClass(),
						'priority' => $instance->priority,
					];
				}
			}
		}
		$this->containers = array_map(fn($item) => $item['class'], $containers);
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