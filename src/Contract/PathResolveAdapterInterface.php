<?php
declare(strict_types=1);

namespace SuperKernel\Di\Contract;

interface PathResolveAdapterInterface
{
	public function supports(): bool;

	public function resolve(): string;
}