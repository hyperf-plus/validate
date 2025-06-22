<?php

declare(strict_types=1);

// 设置时区
date_default_timezone_set('Asia/Shanghai');

// 引入 Composer 自动加载
require_once dirname(__DIR__) . '/vendor/autoload.php';

// 设置错误报告
error_reporting(E_ALL);
ini_set('display_errors', '1');

// 设置内存限制
ini_set('memory_limit', '512M'); 