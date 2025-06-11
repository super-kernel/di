<?php
declare(strict_types=1);

namespace SuperKernel\Di\Contract;

interface DefinerInterface
{
	public function getDefinition(string $id): ?DefinitionInterface;
}