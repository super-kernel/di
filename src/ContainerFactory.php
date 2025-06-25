<?php
declare(strict_types=1);

namespace SuperKernel\Di;

use RuntimeException;
use SuperKernel\Contract\ContainerInterface;
use SuperKernel\Di\Annotation\Factory;
use Throwable;

#[Factory]
final class ContainerFactory
{
	private static ?ContainerInterface $container = null;

	public function __construct()
	{
		//  实现额外进程开销完善所有类的扫描及注解类缓存
	}

	public function __invoke(): ContainerInterface
	{
		if (self::$container) {
			return self::$container;
		}

		try {
			return self::$container = new Container()->get(Container::class);
		}
		catch (Throwable $e) {
			throw new RuntimeException($e->getMessage());
		}
	}
}