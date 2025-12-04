<?php

declare(strict_types=1);

<<<<<<< HEAD
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
=======
error_reporting(E_ALL);

! defined('BASE_PATH') && define('BASE_PATH', dirname(__DIR__, 1));

require BASE_PATH . '/vendor/autoload.php'; 
>>>>>>> 6490b4a99ecb2dc9d88003e0d659cdcb6a6dc610
