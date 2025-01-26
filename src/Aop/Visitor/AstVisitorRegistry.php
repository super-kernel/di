<?php
declare(strict_types=1);

namespace SuperKernel\Di\Aop\Visitor;

use SplPriorityQueue;
use const PHP_INT_MAX;

/**
 * @AstVisitorRegistry
 * @\SuperKernel\Di\Parser\AstVisitorRegistry
 */
final class AstVisitorRegistry
{
    static protected ?SplPriorityQueue $queue = null;

    static protected array $visitors = [];

    static public function insert(string $value, int $priority = 0): true
    {
        self::$visitors [] = $value;
        return self::getQueue()->insert($value, $priority);
    }

    static public function exists(string $value): bool
    {
        return in_array($value, self::$visitors);
    }

    static public function getQueue(): SplPriorityQueue
    {
        return self::$queue ??= new class extends SplPriorityQueue {
            protected int $priority = PHP_INT_MAX;

            public function insert(mixed $value, mixed $priority = 0): true
            {
                return parent::insert($value, [$priority, $this->priority--]);
            }
        };
    }
}