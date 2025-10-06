<?php
declare(strict_types=1);

namespace SuperKernel\Di\Exception;

use Psr\Container\NotFoundExceptionInterface;

final class NotFoundException extends Exception implements NotFoundExceptionInterface
{
}