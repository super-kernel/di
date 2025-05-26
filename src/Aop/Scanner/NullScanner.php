<?php
declare(strict_types=1);

namespace SuperKernel\Di\Aop\Scanner;

use SuperKernel\Di\Aop\Scanned;
use SuperKernel\Di\Contract\ScannerInterface;

final class NullScanner implements ScannerInterface
{
	/**
	 * @return Scanned
	 */
	public function scan(): Scanned
	{
		// TODO: Implement scan() method.
	}
}