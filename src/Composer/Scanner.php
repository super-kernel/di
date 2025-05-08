<?php
declare(strict_types=1);

namespace SuperKernel\Di\Composer;

use Psr\Container\ContainerInterface;
use SuperKernel\Di\Container;
use SuperKernel\Di\Interface\DefinitionFactoryInterface;
use SuperKernel\Di\Interface\ScanHandlerInterface;

/**
 * @Scanner
 * @\SuperKernel\Di\Composer\Scanner
 */
final readonly class Scanner
{
	private ContainerInterface $container;

	public function __construct(DefinitionFactoryInterface $definitionFactory)
	{
		$this->container = new Container($definitionFactory);

		$this->scan();
	}

	/** @noinspection PhpUnhandledExceptionInspection */
	public function scan(): void
	{
		$this->container->get(ScanHandlerInterface::class)->scan();
	}

	private function getProxyDir(): string
	{
		return ComposerFactory::getRootPath() . '/runtime/proxy';
	}

	/** @noinspection PhpUndefinedFieldInspection */
	public function __invoke(): DefinitionFactoryInterface
	{
		return (fn() => $this->definitionFactory)->call($this->container);
	}
}