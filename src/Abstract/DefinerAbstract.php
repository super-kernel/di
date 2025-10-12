<?php
declare(strict_types=1);

namespace SuperKernel\Di\Abstract;

use SuperKernel\Attribute\Contract;
use SuperKernel\Di\Collector\ReflectionManager;

abstract class DefinerAbstract
{
	private array $entries = [];

	/**
	 * For different types of item judgments, items that can be directly identified by the definer are given.
	 *
	 * @param string $id
	 *
	 * @return string|null
	 */
	final protected function getRealEntry(string $id): ?string
	{
		if (isset($this->entries[$id]) || array_key_exists($id, $this->entries)) {
			return $this->entries[$id];
		}

		/* @var array<string> $annotations */
		$classes = ReflectionManager::getAttributes(Contract::class);

		$priority  = 0;
		$classname = null;

		foreach ($classes as $class) {
			$reflectionAttributes = ReflectionManager::getClassAnnotations($class, Contract::class);

			if (empty($reflectionAttributes)) {
				continue;
			}

			foreach ($reflectionAttributes as $reflectionAttribute) {
				/* @var Contract $attributeInstance */
				$attributeInstance = $reflectionAttribute->newInstance();

				if ($attributeInstance->class !== $id) {
					continue;
				}

				if ($attributeInstance->priority >= $priority) {
					$classname = $class;
					$priority  = $attributeInstance->priority;
				}
			}
		}

		if (null === $classname && !interface_exists($id)) {
			return $id;
		}

		return $this->entries[$id] = $classname;
	}
}