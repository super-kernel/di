<?php
declare(strict_types=1);

namespace SuperKernel\Di\Contract;

interface ScanHandlerInterface
{
	public function scan(): ScannedInterface;
}