<?php
declare(strict_types=1);

namespace SuperKernel\Di\Scan;

use SuperKernel\Di\Contract\ScannedInterface;

final readonly class Scanned implements ScannedInterface
{
	public function __construct(private bool $scanned)
	{
	}

	public function isScanned(): bool
	{
		return $this->scanned;
	}
}