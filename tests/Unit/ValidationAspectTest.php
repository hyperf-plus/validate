<?php

declare(strict_types=1);

namespace HPlus\Validate\Tests\Unit;

use HPlus\Validate\Annotations\RequestValidation;
use HPlus\Validate\Aspect\ValidationAspect;
use HPlus\Validate\Exception\ValidateException;
use HPlus\Validate\Tests\TestCase;
use Hyperf\Di\Aop\ProceedingJoinPoint;
use Hyperf\Di\Aop\AnnotationMetadata;
use Mockery;

/**
 * ValidationAspect 单元测试
 */
class ValidationAspectTest extends TestCase
{
    protected ValidationAspect $aspect;

    protected function setUp(): void
    {
        parent::setUp();
        ValidationAspect::clearCache();
    }

    protected function tearDown(): void
    {
        ValidationAspect::clearCache();
        parent::tearDown();
    }

    /**
     * 测试验证通过
     */
    public function testValidationPass(): void
    {
        echo "\n[单元测试] ValidationAspect - 验证通过\n";
        echo "数据: name => 'John', email => 'john@example.com'\n";
        echo "规则: name => required|string, email => required|email\n";
        
        $container = $this->createContainerWithRequest(
            [],
            ['name' => 'John', 'email' => 'john@example.com']
        );

        $aspect = new ValidationAspect(
            $container,
            $container->get(\Hyperf\Validation\Contract\ValidatorFactoryInterface::class)
        );

        $joinPoint = $this->createMockJoinPoint(
            'TestClass',
            'testMethod',
            [
                new RequestValidation(
                    rules: [
                        'name' => 'required|string',
                        'email' => 'required|email',
                    ]
                )
            ]
        );

        $result = $aspect->process($joinPoint);
        echo "✅ 验证通过，返回: " . $result . "\n";
        
        $this->assertEquals('processed', $result);
    }

    /**
     * 测试验证失败 - required 规则
     */
    public function testValidationFailsRequired(): void
    {
        $this->expectException(ValidateException::class);

        $container = $this->createContainerWithRequest([], []);

        $aspect = new ValidationAspect(
            $container,
            $container->get(\Hyperf\Validation\Contract\ValidatorFactoryInterface::class)
        );

        $joinPoint = $this->createMockJoinPoint(
            'TestClass',
            'testMethod',
            [
                new RequestValidation(
                    rules: ['name' => 'required']
                )
            ]
        );

        $aspect->process($joinPoint);
    }

    /**
     * 测试验证失败 - email 规则
     */
    public function testValidationFailsEmail(): void
    {
        $this->expectException(ValidateException::class);
        $this->expectExceptionMessage('email 必须是有效的电子邮件地址');

        $container = $this->createContainerWithRequest(
            [],
            ['email' => 'invalid-email']
        );

        $aspect = new ValidationAspect(
            $container,
            $container->get(\Hyperf\Validation\Contract\ValidatorFactoryInterface::class)
        );

        $joinPoint = $this->createMockJoinPoint(
            'TestClass',
            'testMethod',
            [
                new RequestValidation(
                    rules: ['email' => 'required|email']
                )
            ]
        );

        $aspect->process($joinPoint);
    }

    /**
     * 测试 JSON 模式（默认）
     */
    public function testJsonMode(): void
    {
        $container = $this->createContainerWithRequest(
            ['query' => 'value'],
            ['body' => 'value']
        );

        $aspect = new ValidationAspect(
            $container,
            $container->get(\Hyperf\Validation\Contract\ValidatorFactoryInterface::class)
        );

        $joinPoint = $this->createMockJoinPoint(
            'TestClass',
            'testMethod',
            [
                new RequestValidation(
                    rules: ['body' => 'required'],
                    mode: 'json'
                )
            ]
        );

        $result = $aspect->process($joinPoint);
        $this->assertEquals('processed', $result);
    }

    /**
     * 测试 Query 模式
     */
    public function testQueryMode(): void
    {
        $container = $this->createContainerWithRequest(
            ['page' => '1', 'size' => '10'],
            []
        );

        $aspect = new ValidationAspect(
            $container,
            $container->get(\Hyperf\Validation\Contract\ValidatorFactoryInterface::class)
        );

        $joinPoint = $this->createMockJoinPoint(
            'TestClass',
            'testMethod',
            [
                new RequestValidation(
                    rules: [
                        'page' => 'required|integer',
                        'size' => 'required|integer',
                    ],
                    mode: 'query'
                )
            ]
        );

        $result = $aspect->process($joinPoint);
        $this->assertEquals('processed', $result);
    }

    /**
     * 测试 All 模式
     */
    public function testAllMode(): void
    {
        $container = $this->createContainerWithRequest(
            ['query_param' => 'query_value'],
            ['body_param' => 'body_value']
        );

        $aspect = new ValidationAspect(
            $container,
            $container->get(\Hyperf\Validation\Contract\ValidatorFactoryInterface::class)
        );

        $joinPoint = $this->createMockJoinPoint(
            'TestClass',
            'testMethod',
            [
                new RequestValidation(
                    rules: [
                        'query_param' => 'required',
                        'body_param' => 'required',
                    ],
                    mode: 'all'
                )
            ]
        );

        $result = $aspect->process($joinPoint);
        $this->assertEquals('processed', $result);
    }

    /**
     * 测试自定义错误消息
     */
    public function testCustomMessages(): void
    {
        $this->expectException(ValidateException::class);
        $this->expectExceptionMessage('自定义错误消息');

        $container = $this->createContainerWithRequest([], []);

        $aspect = new ValidationAspect(
            $container,
            $container->get(\Hyperf\Validation\Contract\ValidatorFactoryInterface::class)
        );

        $joinPoint = $this->createMockJoinPoint(
            'TestClass',
            'testMethod',
            [
                new RequestValidation(
                    rules: ['name' => 'required'],
                    messages: ['name.required' => '自定义错误消息']
                )
            ]
        );

        $aspect->process($joinPoint);
    }

    /**
     * 测试自定义字段名称
     */
    public function testCustomAttributes(): void
    {
        $this->expectException(ValidateException::class);
        $this->expectExceptionMessage('用户名');

        $container = $this->createContainerWithRequest([], []);

        $aspect = new ValidationAspect(
            $container,
            $container->get(\Hyperf\Validation\Contract\ValidatorFactoryInterface::class)
        );

        $joinPoint = $this->createMockJoinPoint(
            'TestClass',
            'testMethod',
            [
                new RequestValidation(
                    rules: ['name' => 'required'],
                    attributes: ['name' => '用户名']
                )
            ]
        );

        $aspect->process($joinPoint);
    }

    /**
     * 测试规则缓存
     */
    public function testRuleCache(): void
    {
        $container = $this->createContainerWithRequest(
            [],
            ['name' => 'John']
        );

        $aspect = new ValidationAspect(
            $container,
            $container->get(\Hyperf\Validation\Contract\ValidatorFactoryInterface::class)
        );

        $joinPoint = $this->createMockJoinPoint(
            'TestClass',
            'testMethod',
            [
                new RequestValidation(
                    rules: ['name' => 'required']
                )
            ]
        );

        // 第一次调用 - cache miss
        $stats1 = ValidationAspect::getCacheStats();
        $this->assertEquals(0, $stats1['total']);

        $aspect->process($joinPoint);

        $stats2 = ValidationAspect::getCacheStats();
        $this->assertEquals(1, $stats2['total']);
        $this->assertEquals(1, $stats2['misses']);
        $this->assertEquals(0, $stats2['hits']);

        // 第二次调用 - cache hit
        $aspect->process($joinPoint);

        $stats3 = ValidationAspect::getCacheStats();
        $this->assertEquals(2, $stats3['total']);
        $this->assertEquals(1, $stats3['misses']);
        $this->assertEquals(1, $stats3['hits']);
        $this->assertStringContainsString('50', $stats3['hit_rate']);
    }

    /**
     * 测试清空缓存
     */
    public function testClearCache(): void
    {
        $container = $this->createContainerWithRequest(
            [],
            ['name' => 'John']
        );

        $aspect = new ValidationAspect(
            $container,
            $container->get(\Hyperf\Validation\Contract\ValidatorFactoryInterface::class)
        );

        $joinPoint = $this->createMockJoinPoint(
            'TestClass',
            'testMethod',
            [
                new RequestValidation(
                    rules: ['name' => 'required']
                )
            ]
        );

        $aspect->process($joinPoint);

        $statsBefore = ValidationAspect::getCacheStats();
        $this->assertGreaterThan(0, $statsBefore['total']);

        ValidationAspect::clearCache();

        $statsAfter = ValidationAspect::getCacheStats();
        $this->assertEquals(0, $statsAfter['total']);
        $this->assertEquals(0, $statsAfter['hits']);
        $this->assertEquals(0, $statsAfter['misses']);
    }

    /**
     * 测试无验证注解时直接执行
     */
    public function testNoValidationAnnotation(): void
    {
        $container = $this->createContainerWithRequest();

        $aspect = new ValidationAspect(
            $container,
            $container->get(\Hyperf\Validation\Contract\ValidatorFactoryInterface::class)
        );

        $joinPoint = $this->createMockJoinPoint(
            'TestClass',
            'testMethod',
            [] // 无注解
        );

        $result = $aspect->process($joinPoint);
        $this->assertEquals('processed', $result);
    }

    /**
     * 测试停止首个失败
     */
    public function testStopOnFirstFailure(): void
    {
        $this->expectException(ValidateException::class);

        $container = $this->createContainerWithRequest([], []);

        $aspect = new ValidationAspect(
            $container,
            $container->get(\Hyperf\Validation\Contract\ValidatorFactoryInterface::class)
        );

        $joinPoint = $this->createMockJoinPoint(
            'TestClass',
            'testMethod',
            [
                new RequestValidation(
                    rules: [
                        'field1' => 'required',
                        'field2' => 'required',
                        'field3' => 'required',
                    ],
                    stopOnFirstFailure: true
                )
            ]
        );

        try {
            $aspect->process($joinPoint);
        } catch (ValidateException $e) {
            // 应该只有一个字段的错误
            $this->assertStringContainsString('field1', $e->getMessage());
            throw $e;
        }
    }

    /**
     * 创建 Mock JoinPoint
     */
    protected function createMockJoinPoint(
        string $className,
        string $methodName,
        array $annotations
    ): ProceedingJoinPoint {
        $joinPoint = Mockery::mock(ProceedingJoinPoint::class);
        
        $joinPoint->className = $className;
        $joinPoint->methodName = $methodName;
        
        $metadata = Mockery::mock(AnnotationMetadata::class);
        $metadata->method = $annotations;
        
        $joinPoint->shouldReceive('getAnnotationMetadata')
            ->andReturn($metadata);
        
        $joinPoint->shouldReceive('process')
            ->andReturn('processed');
        
        return $joinPoint;
    }
}