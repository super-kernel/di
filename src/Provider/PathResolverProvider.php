<?php
declare(strict_types=1);

namespace SuperKernel\Di\Provider;

use Generator;
use RuntimeException;
use SuperKernel\Contract\PathResolverInterface;
use SuperKernel\Di\Contract\PathResolveAdapterInterface;
use SuperKernel\Di\PathResolver\Adapter\ComposerAdapter;
use SuperKernel\Di\PathResolver\Adapter\PharAdapter;
use SuperKernel\Di\PathResolver\Adapter\StandardAdapter;
use SuperKernel\Di\PathResolver\PathResolver;

final class PathResolverProvider
{
	private static PathResolverProvider $pathResolverProvider;

	private PathResolveAdapterInterface $resolveAdapter;

	public function __construct()
	{
		$this->resolveAdapter = $this->getResolver();
	}

	public static function make(?string $segment = null): PathResolverInterface
	{
		if (!isset(self::$pathResolverProvider)) {
			self::$pathResolverProvider = new self();
		}

		$pathResolver = self::$pathResolverProvider->__invoke();

		if (null === $segment) {
			return $pathResolver;
		}

		return $pathResolver->to($segment);
	}

	public function __invoke(): PathResolverInterface
	{
		return new PathResolver($this->resolveAdapter->resolve());
	}

	private function getResolver(): PathResolveAdapterInterface
	{
		foreach ($this->getResolvers() as $resolveAdapter) {
			if ($resolveAdapter->supports()) {
				return $resolveAdapter;
			}
		}

		throw new RuntimeException('No resolver suitable for the current environment was found.');
	}

	private function getResolvers(): Generator
	{
		yield new ComposerAdapter();
		yield new PharAdapter();
		yield new StandardAdapter();
	}
}