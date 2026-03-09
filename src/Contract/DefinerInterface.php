<?php
declare(strict_types=1);

namespace SuperKernel\Di\Contract;

interface DefinerInterface
{
	public function support(string $id): bool;

	public function create(string $id): DefinitionInterface;
}