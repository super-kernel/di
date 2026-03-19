<?php
declare(strict_types=1);

namespace SuperKernel\Di\Contract;

use SuperKernel\Contract\PathResolverInterface;

interface PackageInterface
{
	public function getPathResolver(): PathResolverInterface;

	public function getName(): string;

	public function getType(): string;

	public function getReference(): ?string;

	public function getAutoload(): array;

	public function getDevAutoload(): array;
}
