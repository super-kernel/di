<?php
declare(strict_types=1);

namespace SuperKernel\Di\Interface;

/**
 * @DefinitionFactoryInterface
 * @\SuperKernel\Di\Interface\DefinitionFactoryInterface
 */
interface DefinitionFactoryInterface
{
    public function getDefinition(string $id): ?DefinitionInterface;
}