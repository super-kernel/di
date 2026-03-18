<?php
declare(strict_types=1);

namespace SuperKernel\Di\PathResolver\Adapter;

use RuntimeException;
use SuperKernel\Di\Contract\PathResolveAdapterInterface;
use function dirname;
use function getenv;

final class ComposerAdapter implements PathResolveAdapterInterface
{
	public function supports(): bool
	{
		return getenv('COMPOSER_BINARY') !== false
		       || getenv('COMPOSER_RUNTIME_BIN_DIR') !== false;
	}

	public function resolve(): string
	{
		$binDir = getenv('COMPOSER_RUNTIME_BIN_DIR');

		if ($binDir) {
			return dirname($binDir, 2);
		}

		throw new RuntimeException('Composer environment detected but root path could not be inferred.');
	}
}