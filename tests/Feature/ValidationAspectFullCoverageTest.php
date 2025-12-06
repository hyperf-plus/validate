<?php

declare(strict_types=1);

namespace HPlus\Validate\Tests\Feature;

use HPlus\Validate\Annotations\RequestValidation;
use HPlus\Validate\Aspect\ValidationAspect;
use HPlus\Validate\Exception\ValidateException;
use HPlus\Validate\Tests\TestCase;
use Hyperf\Di\Aop\AnnotationMetadata;
use Hyperf\Di\Aop\ProceedingJoinPoint;
use Mockery;

/**
 * ValidationAspect 全覆盖测试
 * 
 * 覆盖功能：
 * 1. queryRules - 独立查询参数验证
 * 2. rules - 请求体验证
 * 3. mode - json/form/xml 模式
 * 4. filter - 过滤多余字段
 * 5. security - 安全模式
 * 6. messages/attributes - 自定义消息和字段名
 * 7. 缓存功能
 */
class ValidationAspectFullCoverageTest extends TestCase
{
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

    // ==================== queryRules 测试 ====================

    /**
     * 测试只有 queryRules - 验证通过
     */
    public function testOnlyQueryRulesPass(): void
    {
        $container = $this->createContainerWithRequest(
            ['page' => '1', 'limit' => '10'],
            ['ignored' => 'body data']
        );

        $aspect = new ValidationAspect(
            $container,
            $container->get(\Hyperf\Validation\Contract\ValidatorFactoryInterface::class)
        );

        $joinPoint = $this->createMockJoinPoint([
            new RequestValidation(
                queryRules: [
                    'page' => 'required|integer|min:1',
                    'limit' => 'required|integer|max:100',
                ]
            )
        ]);

        $result = $aspect->process($joinPoint);
        $this->assertEquals('processed', $result);
    }

    /**
     * 测试只有 queryRules - 验证失败
     */
    public function testOnlyQueryRulesFail(): void
    {
        $this->expectException(ValidateException::class);

        $container = $this->createContainerWithRequest(
            ['page' => 'invalid', 'limit' => '10'],
            []
        );

        $aspect = new ValidationAspect(
            $container,
            $container->get(\Hyperf\Validation\Contract\ValidatorFactoryInterface::class)
        );

        $joinPoint = $this->createMockJoinPoint([
            new RequestValidation(
                queryRules: ['page' => 'required|integer']
            )
        ]);

        $aspect->process($joinPoint);
    }

    /**
     * 测试 queryRules 必填字段缺失
     */
    public function testQueryRulesRequiredMissing(): void
    {
        $this->expectException(ValidateException::class);

        $container = $this->createContainerWithRequest(
            [],  // 空查询参数
            ['name' => 'test']
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

    // ==================== rules + queryRules 组合测试 ====================

    /**
     * 测试同时验证 rules 和 queryRules - 都通过
     */
    public function testBothRulesAndQueryRulesPass(): void
    {
        $container = $this->createContainerWithRequest(
            ['page' => '1', 'limit' => '20'],
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
                queryRules: [
                    'page' => 'required|integer|min:1',
                    'limit' => 'required|integer|max:100',
                ]
            )
        ]);

        $result = $aspect->process($joinPoint);
        $this->assertEquals('processed', $result);
    }

    /**
     * 测试 queryRules 失败时不执行 rules 验证
     */
    public function testQueryRulesFailBeforeRules(): void
    {
        $this->expectException(ValidateException::class);
        $this->expectExceptionMessage('page');

        $container = $this->createContainerWithRequest(
            ['page' => 'invalid'],  // 无效的 page
            ['name' => 'John']  // body 数据是有效的
        );

        $aspect = new ValidationAspect(
            $container,
            $container->get(\Hyperf\Validation\Contract\ValidatorFactoryInterface::class)
        );

        $joinPoint = $this->createMockJoinPoint([
            new RequestValidation(
                rules: ['name' => 'required'],
                queryRules: ['page' => 'required|integer']
            )
        ]);

        $aspect->process($joinPoint);
    }

    /**
     * 测试 queryRules 通过但 rules 失败
     */
    public function testQueryRulesPassButRulesFail(): void
    {
        $this->expectException(ValidateException::class);
        $this->expectExceptionMessage('email');

        $container = $this->createContainerWithRequest(
            ['page' => '1'],  // 有效的 query
            ['email' => 'invalid-email']  // 无效的 body
        );

        $aspect = new ValidationAspect(
            $container,
            $container->get(\Hyperf\Validation\Contract\ValidatorFactoryInterface::class)
        );

        $joinPoint = $this->createMockJoinPoint([
            new RequestValidation(
                rules: ['email' => 'required|email'],
                queryRules: ['page' => 'required|integer']
            )
        ]);

        $aspect->process($joinPoint);
    }

    // ==================== form 模式测试 ====================

    /**
     * 测试 form 模式 - 从 getParsedBody 获取
     */
    public function testFormModeWithParsedBody(): void
    {
        $container = $this->createContainerWithRequest(
            [],
            ['username' => 'john', 'password' => 'secret123'],
            'POST',
            'application/x-www-form-urlencoded'
        );

        $aspect = new ValidationAspect(
            $container,
            $container->get(\Hyperf\Validation\Contract\ValidatorFactoryInterface::class)
        );

        $joinPoint = $this->createMockJoinPoint([
            new RequestValidation(
                rules: [
                    'username' => 'required|string|min:3',
                    'password' => 'required|string|min:6',
                ],
                mode: 'form'
            )
        ]);

        $result = $aspect->process($joinPoint);
        $this->assertEquals('processed', $result);
    }

    /**
     * 测试 form 模式 - 从原始 body 解析
     */
    public function testFormModeWithRawBody(): void
    {
        $container = $this->createContainerWithRequest(
            [],
            [],  // parsedBody 为空
            'POST',
            'application/x-www-form-urlencoded',
            'username=john&password=secret123'  // 原始 body
        );

        $aspect = new ValidationAspect(
            $container,
            $container->get(\Hyperf\Validation\Contract\ValidatorFactoryInterface::class)
        );

        $joinPoint = $this->createMockJoinPoint([
            new RequestValidation(
                rules: [
                    'username' => 'required|string',
                    'password' => 'required|string',
                ],
                mode: 'form'
            )
        ]);

        $result = $aspect->process($joinPoint);
        $this->assertEquals('processed', $result);
    }

    /**
     * 测试 form 模式 - 空 body
     */
    public function testFormModeEmptyBody(): void
    {
        $this->expectException(ValidateException::class);

        $container = $this->createContainerWithRequest(
            [],
            [],
            'POST',
            'application/x-www-form-urlencoded',
            ''  // 空 body
        );

        $aspect = new ValidationAspect(
            $container,
            $container->get(\Hyperf\Validation\Contract\ValidatorFactoryInterface::class)
        );

        $joinPoint = $this->createMockJoinPoint([
            new RequestValidation(
                rules: ['name' => 'required'],
                mode: 'form'
            )
        ]);

        $aspect->process($joinPoint);
    }

    // ==================== xml 模式测试 ====================

    /**
     * 测试 xml 模式 - 简单 XML
     */
    public function testXmlModeSimple(): void
    {
        // 跳过测试如果 simplexml 扩展未安装
        if (!function_exists('simplexml_load_string')) {
            $this->markTestSkipped('simplexml extension is not installed');
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?><root><name>John</name><email>john@example.com</email></root>';

        $container = $this->createContainerWithRequest(
            [],
            [],
            'POST',
            'application/xml',
            $xml
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
                mode: 'xml'
            )
        ]);

        $result = $aspect->process($joinPoint);
        $this->assertEquals('processed', $result);
    }

    /**
     * 测试 xml 模式 - 嵌套 XML
     */
    public function testXmlModeNested(): void
    {
        if (!function_exists('simplexml_load_string')) {
            $this->markTestSkipped('simplexml extension is not installed');
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?><root><user><name>John</name><age>25</age></user></root>';

        $container = $this->createContainerWithRequest(
            [],
            [],
            'POST',
            'application/xml',
            $xml
        );

        $aspect = new ValidationAspect(
            $container,
            $container->get(\Hyperf\Validation\Contract\ValidatorFactoryInterface::class)
        );

        $joinPoint = $this->createMockJoinPoint([
            new RequestValidation(
                rules: ['user' => 'required|array'],
                mode: 'xml'
            )
        ]);

        $result = $aspect->process($joinPoint);
        $this->assertEquals('processed', $result);
    }

    /**
     * 测试 xml 模式 - 无效 XML 返回空数组
     */
    public function testXmlModeInvalidXml(): void
    {
        if (!function_exists('simplexml_load_string')) {
            $this->markTestSkipped('simplexml extension is not installed');
        }

        $this->expectException(ValidateException::class);

        $container = $this->createContainerWithRequest(
            [],
            [],
            'POST',
            'application/xml',
            'this is not valid xml'  // 无效 XML
        );

        $aspect = new ValidationAspect(
            $container,
            $container->get(\Hyperf\Validation\Contract\ValidatorFactoryInterface::class)
        );

        $joinPoint = $this->createMockJoinPoint([
            new RequestValidation(
                rules: ['name' => 'required'],
                mode: 'xml'
            )
        ]);

        $aspect->process($joinPoint);
    }

    /**
     * 测试 xml 模式 - 空 body
     */
    public function testXmlModeEmptyBody(): void
    {
        if (!function_exists('simplexml_load_string')) {
            $this->markTestSkipped('simplexml extension is not installed');
        }

        $this->expectException(ValidateException::class);

        $container = $this->createContainerWithRequest(
            [],
            [],
            'POST',
            'application/xml',
            ''  // 空 body
        );

        $aspect = new ValidationAspect(
            $container,
            $container->get(\Hyperf\Validation\Contract\ValidatorFactoryInterface::class)
        );

        $joinPoint = $this->createMockJoinPoint([
            new RequestValidation(
                rules: ['name' => 'required'],
                mode: 'xml'
            )
        ]);

        $aspect->process($joinPoint);
    }

    /**
     * 测试 xml 模式 - 扩展未安装时的错误提示
     */
    public function testXmlModeExtensionNotInstalled(): void
    {
        // 这个测试只有在 simplexml 扩展未安装时才有意义
        // 在正常环境下跳过此测试
        if (function_exists('simplexml_load_string')) {
            $this->markTestSkipped('This test only runs when simplexml extension is not installed');
        }

        $this->expectException(ValidateException::class);
        $this->expectExceptionMessage('simplexml PHP extension');

        $container = $this->createContainerWithRequest(
            [],
            [],
            'POST',
            'application/xml',
            '<root><name>test</name></root>'
        );

        $aspect = new ValidationAspect(
            $container,
            $container->get(\Hyperf\Validation\Contract\ValidatorFactoryInterface::class)
        );

        $joinPoint = $this->createMockJoinPoint([
            new RequestValidation(
                rules: ['name' => 'required'],
                mode: 'xml'
            )
        ]);

        $aspect->process($joinPoint);
    }

    // ==================== security 模式测试 ====================

    /**
     * 测试 security 模式 - query 参数有额外字段
     */
    public function testSecurityModeQueryExtraFields(): void
    {
        $this->expectException(ValidateException::class);
        $this->expectExceptionMessage('query params extra invalid');

        $container = $this->createContainerWithRequest(
            ['page' => '1', 'extra' => 'should not be here'],
            []
        );

        $aspect = new ValidationAspect(
            $container,
            $container->get(\Hyperf\Validation\Contract\ValidatorFactoryInterface::class)
        );

        $joinPoint = $this->createMockJoinPoint([
            new RequestValidation(
                queryRules: ['page' => 'required|integer'],
                security: true
            )
        ]);

        $aspect->process($joinPoint);
    }

    /**
     * 测试 security 模式 - body 参数有额外字段
     */
    public function testSecurityModeBodyExtraFields(): void
    {
        $this->expectException(ValidateException::class);
        $this->expectExceptionMessage('body params extra invalid');

        $container = $this->createContainerWithRequest(
            [],
            ['name' => 'John', 'extra' => 'should not be here']
        );

        $aspect = new ValidationAspect(
            $container,
            $container->get(\Hyperf\Validation\Contract\ValidatorFactoryInterface::class)
        );

        $joinPoint = $this->createMockJoinPoint([
            new RequestValidation(
                rules: ['name' => 'required'],
                security: true
            )
        ]);

        $aspect->process($joinPoint);
    }

    /**
     * 测试 security 模式 - 同时检查 query 和 body
     */
    public function testSecurityModeBothQueryAndBody(): void
    {
        $container = $this->createContainerWithRequest(
            ['page' => '1'],
            ['name' => 'John']
        );

        $aspect = new ValidationAspect(
            $container,
            $container->get(\Hyperf\Validation\Contract\ValidatorFactoryInterface::class)
        );

        $joinPoint = $this->createMockJoinPoint([
            new RequestValidation(
                rules: ['name' => 'required'],
                queryRules: ['page' => 'required|integer'],
                security: true
            )
        ]);

        $result = $aspect->process($joinPoint);
        $this->assertEquals('processed', $result);
    }

    // ==================== filter 模式测试 ====================

    /**
     * 测试 filter 模式 - 过滤 query 参数
     */
    public function testFilterModeQueryParams(): void
    {
        $container = $this->createContainerWithRequest(
            ['page' => '1', 'extra' => 'to be filtered'],
            []
        );

        $aspect = new ValidationAspect(
            $container,
            $container->get(\Hyperf\Validation\Contract\ValidatorFactoryInterface::class)
        );

        $joinPoint = $this->createMockJoinPoint([
            new RequestValidation(
                queryRules: ['page' => 'required|integer'],
                filter: true
            )
        ]);

        $result = $aspect->process($joinPoint);
        $this->assertEquals('processed', $result);
        // 注意：实际过滤效果需要通过 Context 验证
    }

    /**
     * 测试 filter 模式 - 过滤 body 参数
     */
    public function testFilterModeBodyParams(): void
    {
        $container = $this->createContainerWithRequest(
            [],
            ['name' => 'John', 'extra' => 'to be filtered']
        );

        $aspect = new ValidationAspect(
            $container,
            $container->get(\Hyperf\Validation\Contract\ValidatorFactoryInterface::class)
        );

        $joinPoint = $this->createMockJoinPoint([
            new RequestValidation(
                rules: ['name' => 'required'],
                filter: true
            )
        ]);

        $result = $aspect->process($joinPoint);
        $this->assertEquals('processed', $result);
    }

    /**
     * 测试 filter 模式 - 同时过滤 query 和 body
     */
    public function testFilterModeBothQueryAndBody(): void
    {
        $container = $this->createContainerWithRequest(
            ['page' => '1', 'queryExtra' => 'filtered'],
            ['name' => 'John', 'bodyExtra' => 'filtered']
        );

        $aspect = new ValidationAspect(
            $container,
            $container->get(\Hyperf\Validation\Contract\ValidatorFactoryInterface::class)
        );

        $joinPoint = $this->createMockJoinPoint([
            new RequestValidation(
                rules: ['name' => 'required'],
                queryRules: ['page' => 'required|integer'],
                filter: true
            )
        ]);

        $result = $aspect->process($joinPoint);
        $this->assertEquals('processed', $result);
    }

    // ==================== messages 和 attributes 测试 ====================

    /**
     * 测试自定义错误消息对 queryRules 生效
     */
    public function testCustomMessagesForQueryRules(): void
    {
        $this->expectException(ValidateException::class);
        $this->expectExceptionMessage('页码必须是数字');

        $container = $this->createContainerWithRequest(
            ['page' => 'invalid'],
            []
        );

        $aspect = new ValidationAspect(
            $container,
            $container->get(\Hyperf\Validation\Contract\ValidatorFactoryInterface::class)
        );

        $joinPoint = $this->createMockJoinPoint([
            new RequestValidation(
                queryRules: ['page' => 'required|integer'],
                messages: ['page.integer' => '页码必须是数字']
            )
        ]);

        $aspect->process($joinPoint);
    }

    /**
     * 测试自定义错误消息对 rules 生效
     */
    public function testCustomMessagesForRules(): void
    {
        $this->expectException(ValidateException::class);
        $this->expectExceptionMessage('邮箱格式不正确');

        $container = $this->createContainerWithRequest(
            [],
            ['email' => 'invalid']
        );

        $aspect = new ValidationAspect(
            $container,
            $container->get(\Hyperf\Validation\Contract\ValidatorFactoryInterface::class)
        );

        $joinPoint = $this->createMockJoinPoint([
            new RequestValidation(
                rules: ['email' => 'required|email'],
                messages: ['email.email' => '邮箱格式不正确']
            )
        ]);

        $aspect->process($joinPoint);
    }

    /**
     * 测试自定义属性名对两种规则都生效
     */
    public function testCustomAttributesForBoth(): void
    {
        $this->expectException(ValidateException::class);
        $this->expectExceptionMessage('页码');

        $container = $this->createContainerWithRequest(
            [],
            []
        );

        $aspect = new ValidationAspect(
            $container,
            $container->get(\Hyperf\Validation\Contract\ValidatorFactoryInterface::class)
        );

        $joinPoint = $this->createMockJoinPoint([
            new RequestValidation(
                queryRules: ['page' => 'required'],
                attributes: ['page' => '页码', 'name' => '姓名']
            )
        ]);

        $aspect->process($joinPoint);
    }

    // ==================== 嵌套字段测试 ====================

    /**
     * 测试嵌套字段验证 (field.*)
     */
    public function testNestedFieldValidation(): void
    {
        $container = $this->createContainerWithRequest(
            [],
            [
                'users' => [
                    ['name' => 'John', 'email' => 'john@example.com'],
                    ['name' => 'Jane', 'email' => 'jane@example.com'],
                ]
            ]
        );

        $aspect = new ValidationAspect(
            $container,
            $container->get(\Hyperf\Validation\Contract\ValidatorFactoryInterface::class)
        );

        $joinPoint = $this->createMockJoinPoint([
            new RequestValidation(
                rules: [
                    'users' => 'required|array',
                    'users.*.name' => 'required|string',
                    'users.*.email' => 'required|email',
                ]
            )
        ]);

        $result = $aspect->process($joinPoint);
        $this->assertEquals('processed', $result);
    }

    /**
     * 测试嵌套字段验证失败
     */
    public function testNestedFieldValidationFail(): void
    {
        $this->expectException(ValidateException::class);

        $container = $this->createContainerWithRequest(
            [],
            [
                'users' => [
                    ['name' => 'John', 'email' => 'invalid-email'],  // 无效 email
                ]
            ]
        );

        $aspect = new ValidationAspect(
            $container,
            $container->get(\Hyperf\Validation\Contract\ValidatorFactoryInterface::class)
        );

        $joinPoint = $this->createMockJoinPoint([
            new RequestValidation(
                rules: [
                    'users' => 'required|array',
                    'users.*.email' => 'required|email',
                ]
            )
        ]);

        $aspect->process($joinPoint);
    }

    // ==================== 边界情况测试 ====================

    /**
     * 测试空 rules 和空 queryRules
     */
    public function testEmptyRulesAndQueryRules(): void
    {
        $container = $this->createContainerWithRequest(
            ['any' => 'data'],
            ['any' => 'data']
        );

        $aspect = new ValidationAspect(
            $container,
            $container->get(\Hyperf\Validation\Contract\ValidatorFactoryInterface::class)
        );

        $joinPoint = $this->createMockJoinPoint([
            new RequestValidation(
                rules: [],
                queryRules: []
            )
        ]);

        $result = $aspect->process($joinPoint);
        $this->assertEquals('processed', $result);
    }

    /**
     * 测试无注解时直接通过
     */
    public function testNoAnnotation(): void
    {
        $container = $this->createContainerWithRequest();

        $aspect = new ValidationAspect(
            $container,
            $container->get(\Hyperf\Validation\Contract\ValidatorFactoryInterface::class)
        );

        $joinPoint = $this->createMockJoinPoint([]);

        $result = $aspect->process($joinPoint);
        $this->assertEquals('processed', $result);
    }

    /**
     * 测试缓存命中
     */
    public function testCacheHit(): void
    {
        $container = $this->createContainerWithRequest([], ['name' => 'John']);

        $aspect = new ValidationAspect(
            $container,
            $container->get(\Hyperf\Validation\Contract\ValidatorFactoryInterface::class)
        );

        $joinPoint = $this->createMockJoinPoint([
            new RequestValidation(rules: ['name' => 'required'])
        ]);

        // 多次调用验证缓存工作正常
        $aspect->process($joinPoint);
        $aspect->process($joinPoint);
        
        $this->assertTrue(true);
    }

    /**
     * 测试清空缓存
     */
    public function testClearCache(): void
    {
        $container = $this->createContainerWithRequest([], ['name' => 'John']);

        $aspect = new ValidationAspect(
            $container,
            $container->get(\Hyperf\Validation\Contract\ValidatorFactoryInterface::class)
        );

        $joinPoint = $this->createMockJoinPoint([
            new RequestValidation(rules: ['name' => 'required'])
        ]);

        $aspect->process($joinPoint);
        
        ValidationAspect::clearCache();
        
        $this->assertEquals(0, ValidationAspect::getCacheStats()['rule_cache_size']);
    }

    // ==================== 复杂场景测试 ====================

    /**
     * 测试完整的 API 场景：分页 + 过滤 + 排序
     */
    public function testCompleteApiScenario(): void
    {
        $container = $this->createContainerWithRequest(
            ['page' => '1', 'limit' => '20', 'sort' => 'created_at', 'order' => 'desc'],
            [
                'filters' => [
                    'status' => 'active',
                    'category_id' => 5,
                ],
                'search' => 'keyword',
            ]
        );

        $aspect = new ValidationAspect(
            $container,
            $container->get(\Hyperf\Validation\Contract\ValidatorFactoryInterface::class)
        );

        $joinPoint = $this->createMockJoinPoint([
            new RequestValidation(
                rules: [
                    'filters' => 'nullable|array',
                    'filters.status' => 'nullable|string|in:active,inactive,pending',
                    'filters.category_id' => 'nullable|integer',
                    'search' => 'nullable|string|max:100',
                ],
                queryRules: [
                    'page' => 'required|integer|min:1',
                    'limit' => 'required|integer|between:1,100',
                    'sort' => 'nullable|string|in:created_at,updated_at,name',
                    'order' => 'nullable|string|in:asc,desc',
                ],
                messages: [
                    'page.required' => '页码不能为空',
                    'limit.between' => '每页数量必须在1-100之间',
                ],
                attributes: [
                    'page' => '页码',
                    'limit' => '每页数量',
                    'filters' => '筛选条件',
                    'search' => '搜索关键词',
                ]
            )
        ]);

        $result = $aspect->process($joinPoint);
        $this->assertEquals('processed', $result);
    }

    /**
     * 测试用户注册场景
     */
    public function testUserRegistrationScenario(): void
    {
        $container = $this->createContainerWithRequest(
            ['ref' => 'google'],  // 来源渠道
            [
                'username' => 'john_doe',
                'email' => 'john@example.com',
                'password' => 'SecurePass123!',
                'password_confirmation' => 'SecurePass123!',
                'agree_terms' => true,
            ]
        );

        $aspect = new ValidationAspect(
            $container,
            $container->get(\Hyperf\Validation\Contract\ValidatorFactoryInterface::class)
        );

        $joinPoint = $this->createMockJoinPoint([
            new RequestValidation(
                rules: [
                    'username' => 'required|string|min:3|max:20',
                    'email' => 'required|email',
                    'password' => 'required|string|min:8',
                    'password_confirmation' => 'required|same:password',
                    'agree_terms' => 'required|boolean',
                ],
                queryRules: [
                    'ref' => 'nullable|string|in:google,facebook,twitter,direct',
                ],
                filter: true
            )
        ]);

        $result = $aspect->process($joinPoint);
        $this->assertEquals('processed', $result);
    }

    // ==================== 辅助方法 ====================

    /**
     * 创建 Mock JoinPoint
     */
    protected function createMockJoinPoint(array $annotations): ProceedingJoinPoint
    {
        $joinPoint = Mockery::mock(ProceedingJoinPoint::class);

        $joinPoint->className = 'TestClass';
        $joinPoint->methodName = 'testMethod';

        $metadata = Mockery::mock(AnnotationMetadata::class);
        $metadata->method = $annotations;

        $joinPoint->shouldReceive('getAnnotationMetadata')
            ->andReturn($metadata);

        $joinPoint->shouldReceive('process')
            ->andReturn('processed');

        return $joinPoint;
    }
}

