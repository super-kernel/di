<?php
declare(strict_types=1);

namespace SuperKernel\Di\Exception;

use Psr\Container\ContainerExceptionInterface;
use SuperKernel\Di\Interface\DefinitionInterface;
use Throwable;

/**
 * @InvalidDefinitionException
 * @\SuperKernel\Di\Exception\InvalidDefinitionException
 */
class InvalidDefinitionException extends Exception implements ContainerExceptionInterface
{
    static
    public function create(DefinitionInterface $definition, string $message, ?Throwable $previous = null): self
    {
        return new self(
            sprintf('%s' . PHP_EOL . 'Full definition:' . PHP_EOL . '%s', $message, (string)$definition, 0, $previous)
        );
    }
}