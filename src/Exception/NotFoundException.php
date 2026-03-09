<?php
declare(strict_types=1);

namespace SuperKernel\Di\Exception;

use InvalidArgumentException;
use Psr\Container\NotFoundExceptionInterface;

final class NotFoundException extends InvalidArgumentException implements NotFoundExceptionInterface
{
}