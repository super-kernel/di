<?php
declare(strict_types=1);

use SuperKernel\Di\Aop\Ast;
use SuperKernel\Di\Aop\Visitor\AstVisitorRegistry;
use SuperKernel\Di\Aop\Visitor\ClassHandlerVisitor;
use Tests\TestCase;

!defined('BASE_PATH') && define('BASE_PATH', dirname(__DIR__));

$runtime = new \parallel\Runtime();

$future = $runtime->run(function () {

    require_once __DIR__ . '/../vendor/autoload.php';

    $code = file_get_contents(__DIR__ . '/TestCase.php');

    if (!AstVisitorRegistry::exists(ClassHandlerVisitor::class)) {
        AstVisitorRegistry::insert(ClassHandlerVisitor::class);
    }

    $proxyCode = new Ast();
    $newCode = $proxyCode->proxy(TestCase::class);

//    var_dump($newCode);

    file_put_contents(__DIR__ . '/runtime/TestCase.php', $newCode);
    var_dump(class_exists(TestCase::class));

    return new \ReflectionClass(TestCase::class);
});

/**
 * @var ReflectionClass $value
 */
$value = $future->value();

var_dump($value->getFileName());

var_dump('类：', class_exists(TestCase::class));
