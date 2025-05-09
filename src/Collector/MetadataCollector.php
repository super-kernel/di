<?php
declare(strict_types=1);

namespace SuperKernel\Di\Collector;

use SuperKernel\Contract\ComposerInterface;

/**
 * @MetadataCollector
 * @\SuperKernel\Di\Collector\MetadataCollector
 */
final class MetadataCollector
{
	public function __construct(private ComposerInterface $composer)
	{
	}

	public function getAnnotations(string $class): array
	{
		$annotations = [];

		return [];
	}
}