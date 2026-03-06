<?php
declare(strict_types=1);

namespace SuperKernelTest\Di;

use SuperKernel\Annotation\Provider;
use SuperKernel\Contract\ApplicationInterface;

#[
	Provider(ApplicationInterface::class, 2),
]
final class Application extends \Symfony\Component\Console\Application implements ApplicationInterface
{
}