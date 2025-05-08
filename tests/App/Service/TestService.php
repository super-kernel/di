<?php
declare(strict_types=1);

namespace Tests\App\Service;

use SuperKernel\Di\Annotation\Inject;
use Tests\App\Controller\IndexController;
use Tests\App\Library\TestLibrary;

final class TestService
{
	#[Inject]
	protected TestLibrary $testLibrary;

	public function __construct(TestLibrary $indexController)
	{
	}

	public function getTestLibrary(): void
	{
		var_dump(45);
	}
}