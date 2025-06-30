<?php
declare(strict_types=1);

namespace SuperKernel\Di\Aop\Scanner;

use PhpParser\Parser;
use PhpParser\ParserFactory;
use SuperKernel\Di\Container;
use SuperKernel\Di\Contract\ScanHandlerInterface;
use SuperKernel\Di\Exception\NotFoundException;
use SuperKernel\Di\Factory\ScanHandlerFactory;
use Symfony\Component\Filesystem\Filesystem;

final class Scanner
{
	private readonly ScanHandlerInterface $handler;

	protected string $path;

	final protected Parser $parser;

	final protected readonly ConfigProvider $configProvider;

	/**
	 * @param Container      $container
	 * @param Filesystem     $filesystem
	 * @param ConfigProvider $configProvider
	 * @param ParserFactory  $parserFactory
	 *
	 * @throws NotFoundException
	 */
	public function __construct(
		private readonly Container  $container,
		private readonly Filesystem $filesystem,
		ConfigProvider              $configProvider,
		ParserFactory               $parserFactory,
	)
	{
		$this->path           = $configProvider->getRootPath() . '/runtime/container/scan.cache';
		$this->parser         = $parserFactory->createForHostVersion();
		$this->handler        = $this->container->get(ScanHandlerFactory::class);
		$this->configProvider = $configProvider;
	}

	public function scan(): array
	{
		$this->handler->scan();

		return [];
	}

	public function process(): void
	{
		foreach ($this->configProvider->getFinder() as $finder) {
			var_dump($finder);
		}
	}

	private function putCache(mixed $data): void
	{
		if (!$this->filesystem->exists($dir = dirname($this->path))) {
			$this->filesystem->mkdir($dir, 0755);
		}

		$this->filesystem->dumpFile($this->path, $data);
	}
}