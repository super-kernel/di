<?php

declare(strict_types=1);

namespace SuperKernel\Di\Contract;

/**
 * @DefinitionInterface
 * @\SuperKernel\Di\Contract\DefinitionInterface
 */
interface DefinitionInterface
{
	public function getName(): string;

	public function isInstantiable(): bool;

	public function __toString(): string;
}