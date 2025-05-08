<?php
declare(strict_types=1);

namespace SuperKernel\Di\Exception;

use Psr\Container\NotFoundExceptionInterface;

/**
 * @NotFoundException
 * @\SuperKernel\Di\Exception\NotFoundException
 */
final class NotFoundException extends Exception implements NotFoundExceptionInterface
{
}