<?php
declare(strict_types=1);

namespace SuperKernel\Di\Abstract;

use SuperKernel\Di\Container;
use SuperKernel\Di\Contract\ScanHandlerInterface;
use Tests\Aop\Scanner\Scanner;

abstract class ScanHandlerAbstract implements ScanHandlerInterface
{
	final protected ?Scanner $scanner = null {
		get => $this->scanner ??= $this->container->get(Scanner::class);
	}

	public function __construct(private readonly Container $container)
	{
	}
}