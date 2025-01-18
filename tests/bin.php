<?php
declare(strict_types=1);

use SuperKernel\Di\Aop\Ast;
use SuperKernel\Di\Parser\AstVisitorRegistry;
use SuperKernel\Di\Parser\ClassHandlerVisitor;
use SuperKernel\Di\Parser\MethodHandlerVisitor;

use Tests\TestCase;

require_once __DIR__ . '/../vendor/autoload.php';

//class TestCase
//{
//    protected object $instance;
//
//    public function __construct(array $config)
//    {
//        $this->instance = new class (...func_get_args()) {
//            public function __construct(public array $config)
//            {
//                var_dump(__METHOD__);
//            }
//
//            public function action()
//            {
//            }
//        };
//    }
//}
//
//new TestCase([]);
//
//die;

$code = file_get_contents(__DIR__ . '/TestCase.php');

if (!AstVisitorRegistry::exists(ClassHandlerVisitor::class)) {
    AstVisitorRegistry::insert(ClassHandlerVisitor::class);
}

$proxyCode = new Ast();
$newCode = $proxyCode->proxy(TestCase::class);

var_dump($newCode);

file_put_contents(__DIR__ . '/runtime/TestCase.php', $newCode);