<?php
declare(strict_types=1);

use SuperKernel\Di\Abstract\AbstractContainerFactory;
use SuperKernel\Di\ConfigProviderFactory;
use SuperKernel\Di\Container;
use SuperKernel\Di\ContainerFactory;
use SuperKernel\Di\Contract\DefinerFactoryInterface;
use SuperKernel\Di\Definer\FactoryDefiner;
use SuperKernel\Di\Factory\DefinerFactory;
use SuperKernel\Di\Factory\ResolverFactory;
use Tests\Application;

require_once __DIR__ . '/../vendor/autoload.php';

//new class extends AbstractContainerFactory {
//	protected function getDependencies(): array
//	{
//		return new ConfigProviderFactory()()->get('dependencies');
//	}
//}()->get(Application::class)->run();


new Container()->get(Application::class)->run();

new ContainerFactory()()->get(Application::class)->run();