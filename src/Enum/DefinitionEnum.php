<?php
declare(strict_types=1);

namespace SuperKernel\Di\Enum;

enum DefinitionEnum: int
{
	const int Object = 100;

	const int Factory = 200;

	const int Method = 300;

	const int Parameter = 400;
}