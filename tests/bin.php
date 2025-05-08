<?php
declare(strict_types=1);

use SuperKernel\Di\Aop\ProxyManager;
use SuperKernel\Di\ContainerFactory;

require_once __DIR__ . '/../vendor/autoload.php';

$container = new ContainerFactory()();

$container->get(\Tests\App\Controller\IndexController::class)->show();

exit();

$proxyManager = new ProxyManager(
	[
		'Tests\\App\\Controller\\IndexController' => __DIR__ . '/App/Controller/IndexController.php',
		'Tests\\App\\Library\\TestLibrary'        => __DIR__ . '/App/Library/TestLibrary.php',
		'Tests\\App\\Service\\TestList'           => __DIR__ . '/App/Service/TestList.php',
		'Tests\\App\\Service\\TestService'        => __DIR__ . '/App/Service/TestService.php',
	],
);