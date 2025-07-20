<?php
declare(strict_types=1);

namespace Tests;

final readonly class Application
{
	public function __construct()
	{
	}

	public function run()
	{
		var_dump('run');
	}
}