<?php
declare(strict_types=1);

use SuperKernel\Di\Container;
use Tests\Application;

require_once __DIR__ . '/../vendor/autoload.php';

//new class extends AbstractContainerFactory {
//	protected function getDependencies(): array
//	{
//		return new ConfigProviderFactory()()->get('dependencies');
//	}
//}()->get(Application::class)->run();


var_dump(
	new Container()->get(Application::class)
);

//	->run();

//new ContainerFactory()()->get(Application::class)->run();