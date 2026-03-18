<?php
declare(strict_types=1);

namespace SuperKernel\Di\PathResolver\Adapter;

use Phar;
use RuntimeException;
use SuperKernel\Di\Contract\PathResolveAdapterInterface;
use function array_filter;
use function count;
use function dirname;
use function explode;
use function min;
use function realpath;
use function sprintf;
use function str_starts_with;
use function strlen;
use const DIRECTORY_SEPARATOR;

/**
 * Provides a fallback mechanism to detect the project root by backtracking the physical path of the entry script.
 */
final class StandardAdapter implements PathResolveAdapterInterface
{
	public function supports(): bool
	{
		if (!extension_loaded('phar') || '' === Phar::running()) {
			return true;
		}

		throw new RuntimeException(
			'StandardResolveAdapter cannot operate within a Phar environment. Please use PharResolveAdapter instead.',
		);
	}

	public function resolve(): string
	{
		$rawScriptPath = $_SERVER['SCRIPT_FILENAME'];
		$absolutePath  = realpath($rawScriptPath);

		if (!$absolutePath) {
			throw new RuntimeException('Cannot resolve SCRIPT_FILENAME path.');
		}

		$maxSteps = $this->calculateMaxSteps($rawScriptPath);

		$currentDir = dirname($absolutePath);
		$stepsTaken = 0;

		while ($stepsTaken <= $maxSteps) {
			if (file_exists($currentDir . DIRECTORY_SEPARATOR . 'composer.json')) {
				return $currentDir;
			}

			$parent = dirname($currentDir);
			if ($parent === $currentDir) {
				break;
			}

			$currentDir = $parent;
			$stepsTaken++;
		}

		throw new RuntimeException(
			sprintf('Root not detected within %d steps from %s', $maxSteps, $rawScriptPath),
		);
	}

	private function calculateMaxSteps(string $rawPath): int
	{
		$isAbsolute = $this->isAbsolutePath($rawPath);

		$parts        = array_filter(explode(DIRECTORY_SEPARATOR, dirname($rawPath)));
		$segmentCount = count($parts);

		if ($isAbsolute) {
			return min($segmentCount, 3);
		}

		return $segmentCount;
	}

	private function isAbsolutePath(string $path): bool
	{
		return str_starts_with($path, DIRECTORY_SEPARATOR)
		       || (strlen($path) > 3 && $path[1] === ':' && $path[2] === '\\');
	}
}