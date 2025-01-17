<?php
declare(strict_types=1);

namespace SuperKernel\Di\Aop;

/**
 * @ProxyTrait
 * @\SuperKernel\Aop\ProxyTrait
 */
trait ProxyTrait
{
    public function __call(string $name, array $arguments)
    {
        var_dump(
            func_get_args()
        );
    }
}