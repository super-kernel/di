<?php
declare(strict_types=1);

namespace SuperKernel\Di\Definition;

use SuperKernel\Di\Contract\DefinitionInterface;
use function sprintf;

final readonly class FactoryDefinition implements DefinitionInterface
{
	public function __construct(private string $className)
	{
	}

	/**
	 * @return string
	 */
	public function getName(): string
	{
		return $this->className;
	}

	/**
	 * @return string
	 */
	public function __toString(): string
	{
		return sprintf('Factory[%s]', $this->getName());
	}
}