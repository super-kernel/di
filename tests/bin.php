<?php
declare(strict_types=1);

use SuperKernel\Di\Aop\Ast;
use SuperKernel\Di\Parser\AstVisitorRegistry;
use SuperKernel\Di\Parser\MethodHandlerVisitor;
use Tests\TestCase;

require_once __DIR__ . '/../vendor/autoload.php';

$code = file_get_contents(__DIR__ . '/TestCase.php');

if (!AstVisitorRegistry::exists(MethodHandlerVisitor::class)) {
    AstVisitorRegistry::insert(MethodHandlerVisitor::class);
}

$proxyCode = new Ast();
$proxyCode->proxy(TestCase::class);