<?php

declare(strict_types=1);

namespace SuperKernel\Di\Exception;

use InvalidArgumentException;
use Psr\Container\NotFoundExceptionInterface;
use Throwable;

use function sprintf;

final class NotFoundException extends InvalidArgumentException implements NotFoundExceptionInterface
{
    private function __construct(private readonly string $identifier, ?Throwable              $previous = null)
    {
        parent::__construct(sprintf('Container entry "%s" has no definition.', $identifier), 0, $previous);
    }

    public static function forIdentifier(string $identifier, ?Throwable $previous = null): self
    {
        return new self($identifier, $previous);
    }

    public function identifier(): string
    {
        return $this->identifier;
    }
}
