<?php
declare(strict_types=1);

namespace SuperKernel\Di\Aop;

use Composer\Autoload\ClassLoader;
use PhpParser\NodeTraverser;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;
use PhpParser\PrettyPrinterAbstract;
use SuperKernel\Di\Aop\Visitor\AstVisitorRegistry;
use SuperKernel\Di\Aop\Visitor\VisitorMetadata;

/**
 * @Ast
 * @\SuperKernel\Di\Aop\Ast
 */
class Ast
{
    private Parser $astParser;

    private PrettyPrinterAbstract $printer;

    public function __construct()
    {
        $this->astParser = new ParserFactory()->createForHostVersion();
        $this->printer = new Standard();
    }

    public function parse(string $code): ?array
    {
        return $this->astParser->parse($code);
    }

    public function proxy(string $className): string
    {
        $code = $this->getCodeByClassName($className);
        $stmts = $this->astParser->parse($code);
        $traverser = new NodeTraverser();
        $visitorMetadata = new VisitorMetadata($stmts);
        $queue = clone AstVisitorRegistry::getQueue();
        foreach ($queue as $string) {
            $visitor = new $string($visitorMetadata);
            $traverser->addVisitor($visitor);
        }
        $modifiedStmts = $traverser->traverse($stmts);
        return $this->printer->prettyPrintFile($modifiedStmts);
    }

    /**
     * @param string $className
     * @return string
     */
    private function getCodeByClassName(string $className): string
    {
        $composerLoader = ClassLoader::getRegisteredLoaders();

        /** @var ClassLoader $composer */
        $composer = reset($composerLoader);

        $file = $composer->findFile($className);
        if (!$file) {
            return '';
        }
        return file_get_contents($file);
    }
}