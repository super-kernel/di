<?php
declare(strict_types=1);

namespace SuperKernel\Di\ProcessHandler;

use Phar;
use SuperKernel\ComposerResolver\Contract\ScannerInterface;
use function extension_loaded;
use function strlen;

final readonly class PharProcessHandler implements ScannerInterface
{
	public function supports(): bool
	{
		if (!extension_loaded('phar')) {
			return false;
		}

		if (strlen(Phar::running(false)) === 0) {
			return false;
		}

		return true;
	}

	public function execute(callable $task): void
	{
	}
}