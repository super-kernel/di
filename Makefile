# Composer 脚本设置
COMPOSER_CMD=composer dump-autoload -o --classmap-authoritative

# Hyperf 执行器路径
HYPERF=php bin/hyperf.php

# 清理路径
CONTAINER_CACHE=runtime/container

# .PHONY 逻辑目标
.PHONY: prepare-autoload test

prepare-autoload:
	@echo "📦 Generating Composer classmap..."
	$(COMPOSER_CMD)

test:
	php ./tests/bootstrap.php