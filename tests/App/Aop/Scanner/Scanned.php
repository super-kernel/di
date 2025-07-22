<?php
declare(strict_types=1);

namespace Tests\App\Aop\Scanner;

/**
 * @Scanned
 * @\SuperKernel\Di\Composer\ScannerHandler\Scanned
 */
final readonly class Scanned
{
	public function __construct(private bool $canned = true)
	{
	}

	public function isScanned(): bool
	{
		return $this->canned;
	}
}