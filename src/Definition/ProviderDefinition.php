<?php
declare(strict_types=1);

namespace SuperKernel\Di\Definition;

use SuperKernel\Di\Contract\DefinitionInterface;
use function sprintf;

final readonly class ProviderDefinition implements DefinitionInterface
{
	private string $className;

	public function __construct(private string $name, ?string $className = null)
	{
		$this->className = $className;
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function getClassName(): string
	{
		return $this->className;
	}

	public function __toString(): string
	{
		return sprintf('Interface[%s]', $this->getName());
	}
}