<?php
declare(strict_types=1);

namespace SuperKernel\Di\Collector;

use ReflectionAttribute;
use SuperKernel\Di\Attribute\Provider;
use SuperKernel\Di\Constant\AttributeEnum;
use SuperKernel\Di\Contract\AttributeCollectorInterface;
use function array_push;
use function get_object_vars;
use function is_a;
use function property_exists;

#[
	Provider(AttributeCollector::class),
	Provider(AttributeCollectorInterface::class),
]
final class AttributeCollector implements AttributeCollectorInterface
{
	private array $attributes;

	public function setAttribute(string $class, ReflectionAttribute $reflectionAttribute): void
	{
		$this->attributes[$reflectionAttribute->getName()] = new Attribute($class, $reflectionAttribute->newInstance());
	}

	/**
	 * @param string                     $class
	 * @param array<ReflectionAttribute> $attributes
	 *
	 * @return void
	 */
	public function setAttributes(string $class, array $attributes): void
	{
		foreach ($attributes as $attribute) {
			$this->setAttribute($class, $attribute);
		}
	}

	/**
	 * @inheritDoc
	 */
	public function getAttributes(string $attributeName, AttributeEnum $flags = AttributeEnum::EXACT_MATCH): array
	{
		if ($flags === AttributeEnum::EXACT_MATCH) {
			return $this->attributes[$attributeName] ?? [];
		}

		$result = [];

		foreach ($this->attributes as $className => $attributes) {
			if (is_a($className, $attributeName, true)) {
				array_push($result, ...$attributes);
			}
		}

		return $result;
	}

	public function __serialize(): array
	{
		return get_object_vars($this);
	}

	public function __unserialize(array $data): void
	{
		foreach ($data as $name => $value) {
			if (property_exists($this, $name)) {
				$this->{$name} = $value;
			}
		}
	}

	private function __clone(): void
	{
	}
}