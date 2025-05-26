<?php
declare(strict_types=1);

namespace SuperKernel\Di\Aop;

use Phar;
use SuperKernel\Contract\ContainerInterface;
use SuperKernel\Di\Aop\Scanner\PcntlScanner;
use SuperKernel\Di\Aop\Scanner\ProcessScanner;
use SuperKernel\Di\Aop\Scanner\SwooleProcessScanner;
use SuperKernel\Di\Aop\Scanner\NullScanner;
use SuperKernel\Di\Contract\ScannerInterface;

/**
 * @ScannerFactory
 * @\SuperKernel\Di\Aop\ScannerFactory
 */
final class ScannerFactory
{
	public function __invoke(ContainerInterface $container): ScannerInterface
	{
		return $container->get(match (true) {
			!!Phar::running(false)
			        => NullScanner::class,
			extension_loaded('swoole')
			        => SwooleProcessScanner::class,
			!extension_loaded('grpc') && extension_loaded('pcntl')
			        => PcntlScanner::class,
			default => ProcessScanner::class,
		});
	}
}