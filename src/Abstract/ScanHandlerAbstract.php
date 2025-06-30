<?php
declare(strict_types=1);

namespace SuperKernel\Di\Abstract;

use SuperKernel\Di\Aop\Scanner\Scanner;
use SuperKernel\Di\Container;
use SuperKernel\Di\Contract\ScanHandlerInterface;

abstract class ScanHandlerAbstract implements ScanHandlerInterface
{
	final protected ?Scanner $scanner = null {
		get => $this->scanner ??= $this->container->get(Scanner::class);
	}

	public function __construct(private readonly Container $container)
	{
	}
}