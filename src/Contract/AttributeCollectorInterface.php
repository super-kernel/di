<?php
declare(strict_types=1);

namespace SuperKernel\Di\Contract;

use SuperKernel\Di\Collector\Attribute;

interface AttributeCollectorInterface
{
	public const int IS_INSTANCEOF = 2;

	/**
	 * @param string $name  Filter the results to include only
	 *                      [ReflectionAttribute](https://www.php.net/manual/en/class.reflectionattribute.php)
	 *                      instances for attributes matching this class name.
	 * @param int    $flags Flags for determining how to filter the results, if name is provided.
	 *                      Default is 0 which will only return results for attributes that are of the class name.
	 *                      The only other option available, is to use
	 *                      AttributeCollectorInterface::IS_INSTANCEOF,
	 *                      which will instead use instanceof for filtering.
	 *
	 * @return array<Attribute>
	 */
	public function getAttributes(string $name, int $flags = 0): array;
}