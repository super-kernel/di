<?php
declare(strict_types=1);

namespace SuperKernel\Di\PathResolver;

use InvalidArgumentException;
use SuperKernel\Contract\PathResolverInterface;
use function array_filter;
use function array_pop;
use function explode;
use function implode;
use function str_replace;
use function str_starts_with;
use const DIRECTORY_SEPARATOR;

final readonly class PathResolver implements PathResolverInterface
{
	private string $rootPath;

	private string $currentPath;

	public function __construct(string $rootPath, ?string $currentPath = null)
	{
		if ('' === $rootPath) {
			throw new InvalidArgumentException("Root path cannot be empty.");
		}

		$this->rootPath = $rootPath;
		$this->currentPath = $currentPath ?? $rootPath;
	}

	public function to(string $path): self
	{
		$target = $this->currentPath . DIRECTORY_SEPARATOR . $path;
		$normalized = $this->normalize($target);

		if (!str_starts_with($normalized, $this->rootPath)) {
			throw new InvalidArgumentException(
				"Security Breach: Path segment '$path' attempts to escape root directory.",
			);
		}

		return new self($this->rootPath, $normalized);
	}

	public function get(): string
	{
		return $this->currentPath;
	}

	private function normalize(string $path): string
	{
		$path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
		$parts = array_filter(explode(DIRECTORY_SEPARATOR, $path), 'strlen');

		$absolutes = [];
		foreach ($parts as $part) {
			if ('.' === $part) {
				continue;
			}
			if ('..' === $part) {
				array_pop($absolutes);
			} else {
				$absolutes[] = $part;
			}
		}

		$prefix = (DIRECTORY_SEPARATOR === '/') ? DIRECTORY_SEPARATOR : '';
		return $prefix . implode(DIRECTORY_SEPARATOR, $absolutes);
	}
}