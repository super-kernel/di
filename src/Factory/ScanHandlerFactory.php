<?php
declare(strict_types=1);

namespace SuperKernel\Di\Factory;

use Exception;
use SuperKernel\Di\Annotation\Annotation;
use SuperKernel\Di\Annotation\Factory;
use SuperKernel\Di\Aop\Scanner\ConfigProvider;
use SuperKernel\Di\Aop\Scanner\Driver\NullScanHandler;
use SuperKernel\Di\Aop\Scanner\Driver\PcntlScanHandler;
use SuperKernel\Di\Aop\Scanner\Driver\SwooleProcessScanHandler;
use SuperKernel\Di\Container;
use SuperKernel\Di\Contract\ScanHandlerInterface;

#[
	Factory,
	Annotation(ScanHandlerInterface::class),
]
final class ScanHandlerFactory
{
	private ?ScanHandlerInterface $handler = null {
		get => $this->handler ??= $this->container->get(match (true) {
			$this->configProvider->isPharEnabled()
			        => NullScanHandler::class,
			extension_loaded('swoole')
			        => SwooleProcessScanHandler::class,
			extension_loaded('pcntl')
			        => PcntlScanHandler::class,
			default => throw new Exception('No matching scan handler found.'),
		});
	}

	public function __construct(private readonly Container $container, private readonly ConfigProvider $configProvider)
	{
	}

	/**
	 * @return ScanHandlerInterface
	 * @throws Exception
	 */
	public function __invoke(): ScanHandlerInterface
	{
		if ($this->handler instanceof PcntlScanHandler && extension_loaded('grpc')) {
			$grpcForkSupport = ini_get_all('grpc')['local_value'];
			$grpcForkSupport = strtolower(trim(str_replace('0', '', $grpcForkSupport)));
			if (in_array($grpcForkSupport, [
				'',
				'off',
				'false',
			],           true)) {
				throw new Exception(' Grpc fork support must be enabled before the server starts, please set grpc.enable_fork_support = 1 in your php.ini.');
			}
		}

		return $this->handler;
	}
}