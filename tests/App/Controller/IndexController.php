<?php
declare(strict_types=1);

namespace Tests\App\Controller;

use SuperKernel\Di\Annotation\TestCase;
use Tests\App\Service\TestService;

/**
 * @IndexController
 * @\Tests\App\Controller\IndexController
 */
#[
	TestCase
]
readonly class IndexController
{
	private TestService $service;

	public function __construct(TestService $a, ?string $b = null, $bc = null)
	{
		$this->service = $a;
	}

	public function show()
	{
		$this->service->getTestLibrary();

		var_dump(1111);
	}
}