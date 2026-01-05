<?php
declare(strict_types=1);

namespace SuperKernel\Di\Constant;

enum AttributeEnum: int
{
	case EXACT_MATCH = 0;

	case IS_INSTANCEOF = 2;
}
