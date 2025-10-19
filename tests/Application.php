<?php
declare(strict_types=1);

namespace SuperKernelTest\Di;

use SuperKernel\Di\Annotation\Autowired;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
	name: 'test',
)]
final class Application extends Command
{
	#[Autowired]
	private TestService $service;

	public function execute(InputInterface $input, OutputInterface $output): int
	{
		$this->service->test();

		return Command::SUCCESS;
	}
}