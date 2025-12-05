<?php

declare(strict_types=1);

namespace HPlus\Validate\Tests\Feature;

use HPlus\Validate\Annotations\RequestValidation;
use HPlus\Validate\Aspect\ValidationAspect;
use HPlus\Validate\Exception\ValidateException;
use HPlus\Validate\Tests\TestCase;

/**
 * 验证模式功能测试 (json/query/all)
 */
class ValidationModeTest extends TestCase
{
    /**
     * 测试 JSON 模式 - 验证通过
     */
    public function testJsonModePass(): void
    {
        echo "\n[测试] JSON 模式 - 应该通过\n";
        echo "Query参数: ignored => 'value'\n";
        echo "Body参数: name => 'John', email => 'john@example.com'\n";
        echo "模式: json (只验证Body)\n";
        echo "规则: name => required|string, email => required|email\n";
        
        $container = $this->createContainerWithRequest(
            ['ignored' => 'value'],
            ['name' => 'John', 'email' => 'john@example.com']
        );

        $aspect = new ValidationAspect(
            $container,
            $container->get(\Hyperf\Validation\Contract\ValidatorFactoryInterface::class)
        );

        $joinPoint = $this->createMockJoinPoint([
            new RequestValidation(
                rules: [
                    'name' => 'required|string',
                    'email' => 'required|email',
                ],
                mode: 'json'
            )
        ]);

        $result = $aspect->process($joinPoint);
        echo "✅ 验证通过（忽略了Query参数），返回: " . $result . "\n";
        $this->assertEquals('processed', $result);
    }

    /**
     * 测试 JSON 模式 - 忽略查询参数
     */
    public function testJsonModeIgnoresQueryParams(): void
    {
        echo "\n[测试] JSON 模式 - 应该失败（忽略Query参数）\n";
        echo "Query参数: name => 'John'\n";
        echo "Body参数: [] (空)\n";
        echo "模式: json (只验证Body，忽略Query)\n";
        echo "规则: name => required\n";
        echo "期望: 失败，因为Body中没有name\n";
        
        $this->expectException(ValidateException::class);

        $container = $this->createContainerWithRequest(
            ['name' => 'John'],  // query params
            []  // body is empty
        );

        $aspect = new ValidationAspect(
            $container,
            $container->get(\Hyperf\Validation\Contract\ValidatorFactoryInterface::class)
        );

        $joinPoint = $this->createMockJoinPoint([
            new RequestValidation(
                rules: ['name' => 'required'],
                mode: 'json'
            )
        ]);

        try {
            $aspect->process($joinPoint);
            echo "❌ 验证通过了（不应该）\n";
        } catch (ValidateException $e) {
            echo "✅ 验证失败（正确忽略了Query参数）: " . $e->getMessage() . "\n";
            throw $e;
        }
    }

    /**
     * 测试 queryRules - 验证通过
     */
    public function testQueryModePass(): void
    {
        $container = $this->createContainerWithRequest(
            ['page' => '1', 'size' => '10', 'keyword' => 'test'],
            ['ignored' => 'value']
        );

        $aspect = new ValidationAspect(
            $container,
            $container->get(\Hyperf\Validation\Contract\ValidatorFactoryInterface::class)
        );

        $joinPoint = $this->createMockJoinPoint([
            new RequestValidation(
                queryRules: [
                    'page' => 'required|integer|min:1',
                    'size' => 'required|integer|between:1,100',
                    'keyword' => 'nullable|string|max:50',
                ]
            )
        ]);

        $result = $aspect->process($joinPoint);
        $this->assertEquals('processed', $result);
    }

    /**
     * 测试 queryRules 只验证 Query - 忽略请求体
     */
    public function testQueryModeIgnoresBody(): void
    {
        $this->expectException(ValidateException::class);

        $container = $this->createContainerWithRequest(
            [],  // query is empty
            ['page' => '1']  // body params - 不会被 queryRules 使用
        );

        $aspect = new ValidationAspect(
            $container,
            $container->get(\Hyperf\Validation\Contract\ValidatorFactoryInterface::class)
        );

        $joinPoint = $this->createMockJoinPoint([
            new RequestValidation(
                queryRules: ['page' => 'required']
            )
        ]);

        $aspect->process($joinPoint);
    }

    /**
     * 测试同时验证 Query 和 Body 参数
     */
    public function testAllModePass(): void
    {
        $container = $this->createContainerWithRequest(
            ['page' => '1', 'size' => '10'],
            ['filters' => ['status' => 'active']]
        );

        $aspect = new ValidationAspect(
            $container,
            $container->get(\Hyperf\Validation\Contract\ValidatorFactoryInterface::class)
        );

        $joinPoint = $this->createMockJoinPoint([
            new RequestValidation(
                rules: [
                    'filters' => 'nullable|array',
                ],
                queryRules: [
                    'page' => 'required|integer',
                    'size' => 'required|integer',
                ]
            )
        ]);

        $result = $aspect->process($joinPoint);
        $this->assertEquals('processed', $result);
    }

    /**
     * 测试 queryRules - Query 参数验证失败
     */
    public function testAllModeQueryFails(): void
    {
        $this->expectException(ValidateException::class);

        $container = $this->createContainerWithRequest(
            ['page' => 'invalid'],  // 不是整数
            ['filters' => []]
        );

        $aspect = new ValidationAspect(
            $container,
            $container->get(\Hyperf\Validation\Contract\ValidatorFactoryInterface::class)
        );

        $joinPoint = $this->createMockJoinPoint([
            new RequestValidation(
                rules: [
                    'filters' => 'nullable|array',
                ],
                queryRules: [
                    'page' => 'required|integer',
                ]
            )
        ]);

        $aspect->process($joinPoint);
    }

    /**
     * 测试 rules - Body 参数验证失败
     */
    public function testAllModeBodyFails(): void
    {
        $this->expectException(ValidateException::class);

        $container = $this->createContainerWithRequest(
            ['page' => '1'],
            ['filters' => 'not-array']  // 不是数组
        );

        $aspect = new ValidationAspect(
            $container,
            $container->get(\Hyperf\Validation\Contract\ValidatorFactoryInterface::class)
        );

        $joinPoint = $this->createMockJoinPoint([
            new RequestValidation(
                rules: [
                    'filters' => 'required|array',
                ],
                queryRules: [
                    'page' => 'required|integer',
                ]
            )
        ]);

        $aspect->process($joinPoint);
    }

    /**
     * 测试同名字段分别验证
     * queryRules 和 rules 分别验证各自数据源的同名字段
     */
    public function testAllModeParameterOverride(): void
    {
        $container = $this->createContainerWithRequest(
            ['name' => 'QueryName'],
            ['name' => 'BodyName', 'email' => 'test@example.com']
        );

        $aspect = new ValidationAspect(
            $container,
            $container->get(\Hyperf\Validation\Contract\ValidatorFactoryInterface::class)
        );

        $joinPoint = $this->createMockJoinPoint([
            new RequestValidation(
                rules: [
                    'name' => 'required|string|min:8',  // 验证 Body 中的 name (BodyName)
                    'email' => 'required|email',
                ],
                queryRules: [
                    'name' => 'required|string',  // 验证 Query 中的 name (QueryName)
                ]
            )
        ]);

        $result = $aspect->process($joinPoint);
        $this->assertEquals('processed', $result);
    }

    /**
     * 测试默认模式（应该是 json）
     */
    public function testDefaultMode(): void
    {
        $container = $this->createContainerWithRequest(
            ['ignored' => 'value'],
            ['name' => 'John']
        );

        $aspect = new ValidationAspect(
            $container,
            $container->get(\Hyperf\Validation\Contract\ValidatorFactoryInterface::class)
        );

        $joinPoint = $this->createMockJoinPoint([
            new RequestValidation(
                rules: ['name' => 'required']
                // 没有指定 mode，应该默认为 json
            )
        ]);

        $result = $aspect->process($joinPoint);
        $this->assertEquals('processed', $result);
    }

    /**
     * 测试 GET 请求使用 queryRules 验证
     */
    public function testGetRequestWithQueryMode(): void
    {
        $container = $this->createContainerWithRequest(
            ['id' => '123', 'format' => 'json'],
            [],
            'GET'
        );

        $aspect = new ValidationAspect(
            $container,
            $container->get(\Hyperf\Validation\Contract\ValidatorFactoryInterface::class)
        );

        $joinPoint = $this->createMockJoinPoint([
            new RequestValidation(
                queryRules: [
                    'id' => 'required|integer',
                    'format' => 'required|in:json,xml',
                ]
            )
        ]);

        $result = $aspect->process($joinPoint);
        $this->assertEquals('processed', $result);
    }

    /**
     * 测试 POST 请求使用 JSON 模式
     */
    public function testPostRequestWithJsonMode(): void
    {
        $container = $this->createContainerWithRequest(
            [],
            ['username' => 'john_doe', 'email' => 'john@example.com'],
            'POST'
        );

        $aspect = new ValidationAspect(
            $container,
            $container->get(\Hyperf\Validation\Contract\ValidatorFactoryInterface::class)
        );

        $joinPoint = $this->createMockJoinPoint([
            new RequestValidation(
                rules: [
                    'username' => 'required|string|min:3',
                    'email' => 'required|email',
                ],
                mode: 'json'
            )
        ]);

        $result = $aspect->process($joinPoint);
        $this->assertEquals('processed', $result);
    }

    /**
     * 测试复杂场景：搜索接口
     * queryRules: page, size (分页参数)
     * rules: filters, sort (搜索条件)
     */
    public function testComplexSearchScenario(): void
    {
        $container = $this->createContainerWithRequest(
            ['page' => '1', 'size' => '20'],
            [
                'filters' => [
                    ['field' => 'status', 'value' => 'active'],
                    ['field' => 'created_at', 'value' => '2024-01-01'],
                ],
                'sort' => ['field' => 'created_at', 'order' => 'desc'],
            ],
            'POST'
        );

        $aspect = new ValidationAspect(
            $container,
            $container->get(\Hyperf\Validation\Contract\ValidatorFactoryInterface::class)
        );

        $joinPoint = $this->createMockJoinPoint([
            new RequestValidation(
                rules: [
                    'filters' => 'nullable|array',
                    'filters.*.field' => 'required|string',
                    'filters.*.value' => 'required',
                    'sort' => 'nullable|array',
                    'sort.field' => 'required_with:sort|string',
                    'sort.order' => 'required_with:sort|in:asc,desc',
                ],
                queryRules: [
                    'page' => 'required|integer|min:1',
                    'size' => 'required|integer|between:1,100',
                ]
            )
        ]);

        $result = $aspect->process($joinPoint);
        $this->assertEquals('processed', $result);
    }

    /**
     * 辅助方法：创建 Mock JoinPoint
     */
    protected function createMockJoinPoint(array $annotations)
    {
        $joinPoint = \Mockery::mock(\Hyperf\Di\Aop\ProceedingJoinPoint::class);
        $joinPoint->className = 'TestClass';
        $joinPoint->methodName = 'testMethod';
        
        $metadata = \Mockery::mock(\Hyperf\Di\Aop\AnnotationMetadata::class);
        $metadata->method = $annotations;
        
        $joinPoint->shouldReceive('getAnnotationMetadata')->andReturn($metadata);
        $joinPoint->shouldReceive('process')->andReturn('processed');
        
        return $joinPoint;
    }
}
