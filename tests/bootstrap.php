<?php

declare(strict_types=1);

/**
 * PHPUnit Bootstrap File
 */

error_reporting(E_ALL);
date_default_timezone_set('Asia/Shanghai');

// 自动加载
require_once dirname(__DIR__) . '/vendor/autoload.php';

// 设置测试环境变量
! defined('BASE_PATH') && define('BASE_PATH', dirname(__DIR__, 1));

// 初始化 Mockery
if (class_exists('Mockery')) {
    Mockery::globalHelpers();
}