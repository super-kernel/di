<?php
declare(strict_types=1);

namespace SuperKernel\Di\Exception;

use Psr\Container\ContainerExceptionInterface;
use RuntimeException;

abstract class ContainerException extends RuntimeException implements ContainerExceptionInterface
{
}