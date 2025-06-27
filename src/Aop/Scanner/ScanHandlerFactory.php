<?php
declare(strict_types=1);

namespace SuperKernel\Di\Aop\Scanner;

use Phar;
use SuperKernel\Di\Annotation\Factory;
use SuperKernel\Di\Aop\Scanner\Driver\NullScanHandler;
use SuperKernel\Di\Aop\Scanner\Driver\PcntlScanHandler;
use SuperKernel\Di\Aop\Scanner\Driver\ProcessScanHandler;
use SuperKernel\Di\Aop\Scanner\Driver\SwooleProcessScanHandler;
use SuperKernel\Di\Container;
use SuperKernel\Di\Contract\ScanHandlerInterface;

#[Factory]
final class ScanHandlerFactory
{
	public function __invoke(Container $container): ScanHandlerInterface
	{
		return $container->get(match (true) {
			!!Phar::running(false)
			        => NullScanHandler::class,
			extension_loaded('swoole')
			        => SwooleProcessScanHandler::class,
			!extension_loaded('grpc') && extension_loaded('pcntl')
			        => PcntlScanHandler::class,
			default => ProcessScanHandler::class,
		});
	}
}