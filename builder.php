<?php

$pharFile = __DIR__ . '/app.phar'; // 打包出来的 phar 文件名
$sourceDir = __DIR__;              // 要打包的目录（当前目录）
$stubFile = 'index.php';            // 设置启动入口（比如 index.php）

// 如果 phar 已经存在，先删除
if (file_exists($pharFile)) {
	unlink($pharFile);
}

try {
	// 生成 Phar 文件
	$phar = new Phar($pharFile);

	// 将整个目录打包进去，排除一些不必要的文件
	$phar->buildFromDirectory($sourceDir, '/\.(php)$/i');

	// 设置默认启动器（stub）
	$phar->setStub(
		"#!/usr/bin/env php\n" . $phar->createDefaultStub($stubFile)
	);

	echo "Phar 打包完成: {$pharFile}\n";
} catch (Exception $e) {
	echo "Phar 打包失败: " . $e->getMessage() . "\n";
}