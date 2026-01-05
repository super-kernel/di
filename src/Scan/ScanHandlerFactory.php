<?php
declare(strict_types=1);

namespace SuperKernel\Di\Scan;

use Phar;
use SuperKernel\Di\Contract\ScanHandlerInterface;
use SuperKernel\Di\Scan\Handler\NullScanHandler;
use SuperKernel\Di\Scan\Handler\PcntlScanHandler;

final class ScanHandlerFactory
{
	public function __invoke(): ScanHandlerInterface
	{
		return match (true) {
			!Phar::running() => new PcntlScanHandler(),
			default          => new NullScanHandler(),
		};
	}
}