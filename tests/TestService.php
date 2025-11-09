<?php
declare(strict_types=1);

namespace SuperKernelTest\Di;

use SuperKernel\Di\Attribute\Autowired;
use Symfony\Component\Console\Output\OutputInterface;

final class TestService
{
	#[Autowired]
	protected OutputInterface $output;

	public function test(): void
	{
		$this->output->writeln('<info>[INFO] </info>Test Success.');
	}
}