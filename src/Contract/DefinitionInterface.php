<?php
declare(strict_types=1);

namespace SuperKernel\Di\Contract;

interface DefinitionInterface
{
	public function getName(): string;

	public function getClassName(): ?string;

	public function __toString(): string;
}