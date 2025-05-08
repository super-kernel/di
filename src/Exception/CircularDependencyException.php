<?php
declare(strict_types=1);

namespace SuperKernel\Di\Exception;

use RuntimeException;

/**
 * @CircularDependencyException
 * @\SuperKernel\Di\Exception\CircularDependencyException
 */
final class CircularDependencyException extends RuntimeException
{
}