<?php
declare(strict_types=1);

namespace SuperKernel\Di\Interface;

/**
 * @ComposerInterface
 * @\SuperKernel\Di\Interface\ComposerInterface
 */
interface ComposerInterface
{
	public function getDependencies(): array;

	public function getRootPath(): string;

	public function getVendorDir(): string;

	public function getMergedExtra(?string $key = null): array;
}