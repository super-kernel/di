<?php
declare(strict_types=1);

namespace SuperKernel\Di\Scan\Handler;

use Exception;
use SuperKernel\Di\Contract\ScanHandlerInterface;
use SuperKernel\Di\Contract\ScannedInterface;
use SuperKernel\Di\Scan\Scanned;

final class PcntlScanHandler implements ScanHandlerInterface
{
	/**
	 * @return ScannedInterface
	 * @throws Exception
	 */
	public function scan(): ScannedInterface
	{
		$pid = pcntl_fork();
		if ($pid == -1) {
			throw new Exception('The process fork failed');
		}
		if ($pid) {
			pcntl_wait($status);
			if ($status !== 0) {
				exit(-1);
			}

			return new Scanned(true);
		}

		return new Scanned(false);
	}
}