<?php
declare(strict_types=1);

use SuperKernel\Di\Abstract\AbstractContainerFactory;
use SuperKernel\Di\ConfigProviderFactory;
use Tests\Application;

require_once __DIR__ . '/../vendor/autoload.php';

new class extends AbstractContainerFactory {
	protected function getDependencies(): array
	{
		return new ConfigProviderFactory()()->get('dependencies');
	}
}()->get(Application::class)->run();