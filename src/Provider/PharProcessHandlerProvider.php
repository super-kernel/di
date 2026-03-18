<?php
declare(strict_types=1);

namespace SuperKernel\Di\Provider;

use Generator;
use RuntimeException;
use SuperKernel\Annotation\Factory;
use SuperKernel\Annotation\Provider;
use SuperKernel\Contract\ProcessHandlerInterface;
use SuperKernel\Di\ProcessHandler\PcntlProcessHandler;
use SuperKernel\Di\ProcessHandler\PharProcessHandler;

#[
	Provider(ProcessHandlerInterface::class),
	Factory,
]
final class PharProcessHandlerProvider
{
	private static ProcessHandlerInterface $processHandler;

	public function __invoke(): ProcessHandlerInterface
	{
		if (!isset(self::$processHandler)) {
			self::$processHandler = $this->getProcessHandler();
		}
		return self::$processHandler;
	}

	private function getProcessHandler(): ProcessHandlerInterface
	{
		foreach ($this->getProcessHandlers() as $processHandler) {
			if ($processHandler->supports()) {
				return $processHandler;
			}
		}

		throw new RuntimeException('No processHandler found');
	}

	private function getProcessHandlers(): Generator
	{
		yield new PharProcessHandler();
		yield new PcntlProcessHandler();
	}
}