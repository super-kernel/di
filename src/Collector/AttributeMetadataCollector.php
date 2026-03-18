<?php
declare(strict_types=1);

namespace SuperKernel\Di\Collector;

use SuperKernel\Attribute\AttributeMetadata;
use SuperKernel\Contract\AttributeMetadataCollectorInterface;
use SuperKernel\Contract\AttributeMetadataInterface;

final readonly class AttributeMetadataCollector implements AttributeMetadataCollectorInterface
{
	/**
	 * @var array<string, AttributeMetadataInterface> $attributes
	 */
	private array $attributes;

	public function __construct(AttributeMetadata ...$attributesMetadata)
	{
		$attributes = [];
		foreach ($attributesMetadata as $attributeMetadata) {
			foreach ($attributeMetadata->getAttributes() as $attribute) {
				$class = $attribute->getClass();
				if ($attribute->compatible(AttributeMetadataInterface::TARGET_CLASS)) {
					$attributes[$class][AttributeMetadataInterface::TARGET_CLASS][] = $attribute;
				} elseif ($attribute->compatible(AttributeMetadataInterface::TARGET_METHOD)) {
					$attributes[$class][AttributeMetadataInterface::TARGET_METHOD][$attribute->getMethod()][] = $attribute;
				} elseif ($attribute->compatible(AttributeMetadataInterface::TARGET_PROPERTY)) {
					$attributes[$class][AttributeMetadataInterface::TARGET_PROPERTY][$attribute->getProperty()][] = $attribute;
				}
			}
		}

		$this->attributes = $attributes;
	}

	public function getClassAttributes(string $class): array
	{
		return $this->attributes[$class][AttributeMetadataInterface::TARGET_CLASS] ?? [];
	}

	public function getMethodAttributes(string $class, string $method): array
	{
		return $this->attributes[$class][AttributeMetadataInterface::TARGET_METHOD][$method] ?? [];
	}

	public function getPropertyAttributes(string $class, string $property): array
	{
		return $this->attributes[$class][AttributeMetadataInterface::TARGET_PROPERTY][$property] ?? [];
	}

	public function getClassesByAttribute(string $attribute): array
	{
		$attributes = [];

		foreach ($this->attributes as $targets) {
			if (!isset($targets[AttributeMetadataInterface::TARGET_CLASS])) {
				continue;
			}

			/* @var AttributeMetadataInterface $classAttribute */
			foreach ($targets[AttributeMetadataInterface::TARGET_CLASS] ?? [] as $classAttribute) {
				if ($classAttribute->getAttribute() === $attribute) {
					$attributes[] = $classAttribute;
				}
			}
		}

		return $attributes;
	}

	public function getMethodsByAttribute(string $attribute): array
	{
		$attributes = [];

		foreach ($this->attributes as $targets) {
			if (!isset($targets[AttributeMetadataInterface::TARGET_METHOD])) {
				continue;
			}

			foreach ($targets[AttributeMetadataInterface::TARGET_METHOD] ?? [] as $methods) {
				foreach ($methods as $method) {
					if ($method->getAttribute() === $attribute) {
						$attributes[] = $method;
					}
				}
			}
		}

		return $attributes;
	}

	public function getPropertiesByAttribute(string $attribute): array
	{
		$attributes = [];

		foreach ($this->attributes as $targets) {
			if (!isset($targets[AttributeMetadataInterface::TARGET_PROPERTY])) {
				continue;
			}

			foreach ($targets[AttributeMetadataInterface::TARGET_PROPERTY] ?? [] as $properties) {
				foreach ($properties as $property) {
					if ($property->getAttribute() === $attribute) {
						$attributes[] = $property;
					}
				}
			}
		}

		return $attributes;
	}
}