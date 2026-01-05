<?php
declare(strict_types=1);

namespace SuperKernel\Di\Contract;

use ReflectionAttribute;
use SuperKernel\Di\Collector\Attribute;
use SuperKernel\Di\Constant\AttributeEnum;

interface AttributeCollectorInterface
{
	public function setAttribute(string $class, ReflectionAttribute $reflectionAttribute): void;

	/**
	 * @param string                     $class
	 * @param array<ReflectionAttribute> $attributes
	 *
	 * @return void
	 */
	public function setAttributes(string $class, array $attributes): void;

	/**
	 * Returns attribute instances matching the given attribute class name.
	 *
	 * @param string        $attributeName Fully qualified class name of the attribute to match.
	 *
	 * @param AttributeEnum $flags         Controls how the attribute class name is matched.
	 *                                     - AttributeEnum::EXACT_MATCH (default): Only attributes whose class name
	 *                                     exactly matches {@see $attributeName} will be returned.
	 *                                     - AttributeEnum::IS_INSTANCEOF: Attributes whose class is {@see instanceof}
	 *                                     {@see $attributeName} will be returned.
	 *
	 * @return array<Attribute>
	 *         A list of instantiated attribute objects matching the given criteria.
	 */
	public function getAttributes(string $attributeName, AttributeEnum $flags = AttributeEnum::EXACT_MATCH): array;
}