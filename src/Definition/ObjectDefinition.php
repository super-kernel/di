<?php
declare(strict_types=1);

namespace SuperKernel\Di\Definition;

use SuperKernel\Di\Collector\ReflectionManager;
use SuperKernel\Di\Contract\DefinitionInterface;
use SuperKernel\Di\Resolver\ObjectResolver;

/**
 * @ObjectDefinition
 * @\SuperKernel\Di\Definition\ObjectDefinition
 */
final class ObjectDefinition implements DefinitionInterface
{
	private bool $classExists;

	private bool $instantiable;

	public function __construct(private string $name, private ?string $classname = null)
	{
		$this->setClassname($classname ?? $name);
	}

	public function __toString(): string
	{
		return sprintf('Object[%s]', $this->getClassName());
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function setName(string $name): self
	{
		$this->name = $name;
		return $this;
	}

	public function getClassname(): string
	{
		return $this->classname ?? $this->name;
	}

	public function setClassname(?string $classname = null): void
	{
		$this->classname = $classname;

		$this->updateStatusCache();
	}

	public function isClassExists(): bool
	{
		return $this->classExists;
	}

	public function isInstantiable(): bool
	{
		return $this->instantiable;
	}

	private function updateStatusCache(): void
	{
		$classname = $this->getClassname();

		$this->classExists = class_exists($classname) || interface_exists($classname);

		if (!$this->classExists) {
			$this->instantiable = false;
			return;
		}

		$this->instantiable = new ReflectionManager()->reflectClass($classname)->isInstantiable();
	}

	public function getResolver(): string
	{
		return ObjectResolver::class;
	}
}