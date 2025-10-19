<?php
declare(strict_types=1);

namespace SuperKernelTest\Di;

use SuperKernel\Attribute\Factory;
use SuperKernel\Attribute\Provider;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

#[
    Provider(OutputInterface::class),
    Factory,
]
final class LoggerProvider
{
    public function __invoke(): OutputInterface
    {
        return new ConsoleOutput();
    }
}