<?php

declare(strict_types=1);

namespace HPlus\Validate\Tests\Cases;

use PHPUnit\Framework\TestCase;
use Hyperf\Di\Container;
use Hyperf\Context\ApplicationContext;
use Mockery;

abstract class AbstractTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // 设置容器
        $container = Mockery::mock(Container::class);
        ApplicationContext::setContainer($container);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
} 