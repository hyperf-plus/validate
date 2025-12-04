<?php

declare(strict_types=1);

namespace HPlus\Validate\Tests\Performance;

use HPlus\Validate\Annotations\RequestValidation;
use HPlus\Validate\Aspect\ValidationAspect;
use HPlus\Validate\RuleParser;
use HPlus\Validate\Tests\TestCase;

/**
 * 性能测试
 */
class ValidationPerformanceTest extends TestCase
{
    /**
     * 测试规则缓存性能提升
     */
    public function testRuleCachePerformance(): void
    {
        ValidationAspect::clearCache();

        $container = $this->createContainerWithRequest(
            [],
            ['name' => 'John', 'email' => 'john@example.com']
        );

        $aspect = new ValidationAspect(
            $container,
            $container->get(\Hyperf\Validation\Contract\ValidatorFactoryInterface::class)
        );

        $joinPoint = $this->createMockJoinPoint([
            new RequestValidation(
                rules: [
                    'name' => 'required|string|max:50',
                    'email' => 'required|email|max:100',
                ]
            )
        ]);

        // 预热：执行一次
        $aspect->process($joinPoint);

        // 多次调用以获得稳定的平均值
        $iterations = 100;
        $startTime = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            $aspect->process($joinPoint);
        }
        $totalDuration = microtime(true) - $startTime;
        $avgDuration = $totalDuration / $iterations;

        // 平均每次验证应该很快（有缓存）
        $this->assertLessThan(0.001, $avgDuration, '缓存命中时平均每次验证应在1ms内');

        // 验证缓存统计
        $stats = ValidationAspect::getCacheStats();
        $this->assertEquals($iterations + 1, $stats['total']); // 预热1次 + 100次测试
        $this->assertEquals($iterations, $stats['hits']); // 100次都是缓存命中
        $this->assertEquals(1, $stats['misses']); // 只有预热是 miss
        $this->assertGreaterThan(99, ($stats['hits'] / $stats['total']) * 100, '缓存命中率应大于99%');
    }

    /**
     * 测试大量规则的性能
     */
    public function testManyRulesPerformance(): void
    {
        $rules = [];
        for ($i = 1; $i <= 50; $i++) {
            $rules["field{$i}"] = 'required|string|max:100';
        }

        $data = [];
        for ($i = 1; $i <= 50; $i++) {
            $data["field{$i}"] = "value{$i}";
        }

        $container = $this->createContainerWithRequest([], $data);

        $aspect = new ValidationAspect(
            $container,
            $container->get(\Hyperf\Validation\Contract\ValidatorFactoryInterface::class)
        );

        $joinPoint = $this->createMockJoinPoint([
            new RequestValidation(rules: $rules)
        ]);

        $startTime = microtime(true);
        $aspect->process($joinPoint);
        $duration = microtime(true) - $startTime;

        // 50个字段的验证应该在100ms内完成
        $this->assertLessThan(0.1, $duration, '50个字段验证应该在100ms内完成');
    }

    /**
     * 测试嵌套数组验证性能
     */
    public function testNestedArrayPerformance(): void
    {
        $users = [];
        for ($i = 0; $i < 100; $i++) {
            $users[] = [
                'name' => "User{$i}",
                'email' => "user{$i}@example.com",
                'age' => 20 + $i,
            ];
        }

        $container = $this->createContainerWithRequest([], ['users' => $users]);

        $aspect = new ValidationAspect(
            $container,
            $container->get(\Hyperf\Validation\Contract\ValidatorFactoryInterface::class)
        );

        $joinPoint = $this->createMockJoinPoint([
            new RequestValidation(
                rules: [
                    'users' => 'required|array',
                    'users.*.name' => 'required|string|max:50',
                    'users.*.email' => 'required|email',
                    'users.*.age' => 'required|integer|between:1,150',
                ]
            )
        ]);

        $startTime = microtime(true);
        $aspect->process($joinPoint);
        $duration = microtime(true) - $startTime;

        // 100条嵌套记录的验证应该在500ms内完成
        $this->assertLessThan(0.5, $duration, '100条嵌套记录验证应该在500ms内完成');
    }

    /**
     * 测试 RuleParser 缓存性能
     */
    public function testRuleParserCachePerformance(): void
    {
        RuleParser::clearCache();

        $rule = 'required|string|min:3|max:50|regex:/^[A-Za-z0-9_]+$/';

        // 第一次解析（cache miss）
        $startTime1 = microtime(true);
        for ($i = 0; $i < 100; $i++) {
            RuleParser::ruleToJsonSchema($rule);
        }
        $duration1 = microtime(true) - $startTime1;

        // 所有调用都应该命中缓存
        $stats = RuleParser::getCacheStats();
        $this->assertEquals(1, $stats['rule_cache_size']);

        // 验证缓存效果：100次调用应该很快
        $this->assertLessThan(0.01, $duration1, '100次缓存命中应该在10ms内完成');
    }

    /**
     * 测试批量规则转换性能
     */
    public function testBatchRuleConversionPerformance(): void
    {
        $rules = [];
        for ($i = 1; $i <= 100; $i++) {
            $rules["field{$i}|字段{$i}"] = 'required|string|max:100';
        }

        $startTime = microtime(true);
        $schema = RuleParser::rulesToJsonSchema($rules);
        $duration = microtime(true) - $startTime;

        // 100个规则转换应该在50ms内完成
        $this->assertLessThan(0.05, $duration, '100个规则转换应该在50ms内完成');

        // 验证结果
        $this->assertCount(100, $schema['properties']);
        $this->assertCount(100, $schema['required']);
    }

    /**
     * 测试内存使用
     */
    public function testMemoryUsage(): void
    {
        ValidationAspect::clearCache();
        RuleParser::clearCache();

        $memoryBefore = memory_get_usage(true);

        // 创建1000次验证
        $container = $this->createContainerWithRequest([], ['name' => 'John']);
        $aspect = new ValidationAspect(
            $container,
            $container->get(\Hyperf\Validation\Contract\ValidatorFactoryInterface::class)
        );

        for ($i = 0; $i < 1000; $i++) {
            $joinPoint = $this->createMockJoinPoint([
                new RequestValidation(
                    rules: ['name' => 'required|string|max:50']
                )
            ]);
            $aspect->process($joinPoint);
        }

        $memoryAfter = memory_get_usage(true);
        $memoryUsed = $memoryAfter - $memoryBefore;

        // 1000次验证的内存占用应该小于5MB（因为有缓存）
        $this->assertLessThan(5 * 1024 * 1024, $memoryUsed, '1000次验证内存占用应小于5MB');

        // 验证缓存统计
        $stats = ValidationAspect::getCacheStats();
        $this->assertEquals(1000, $stats['total']);
        $this->assertGreaterThan(90, ($stats['hits'] / $stats['total']) * 100, '缓存命中率应大于90%');
    }

    /**
     * 测试并发场景性能
     */
    public function testConcurrentPerformance(): void
    {
        ValidationAspect::clearCache();

        $container = $this->createContainerWithRequest(
            [],
            ['name' => 'John', 'email' => 'john@example.com']
        );

        $aspect = new ValidationAspect(
            $container,
            $container->get(\Hyperf\Validation\Contract\ValidatorFactoryInterface::class)
        );

        // 模拟100个并发请求
        $iterations = 100;
        $startTime = microtime(true);

        for ($i = 0; $i < $iterations; $i++) {
            $joinPoint = $this->createMockJoinPoint([
                new RequestValidation(
                    rules: [
                        'name' => 'required|string|max:50',
                        'email' => 'required|email',
                    ]
                )
            ]);
            $aspect->process($joinPoint);
        }

        $duration = microtime(true) - $startTime;
        $avgDuration = $duration / $iterations;

        // 平均每个请求应该在1ms内完成
        $this->assertLessThan(0.001, $avgDuration, '每个请求平均应在1ms内完成');

        // 验证缓存效果
        $stats = ValidationAspect::getCacheStats();
        $hitRate = ($stats['hits'] / $stats['total']) * 100;
        $this->assertGreaterThanOrEqual(99, $hitRate, '缓存命中率应不低于99%');
    }

    /**
     * 测试复杂规则性能
     */
    public function testComplexRulesPerformance(): void
    {
        $data = [
            'users' => [],
        ];

        // 创建50个用户，每个用户有多个字段
        for ($i = 0; $i < 50; $i++) {
            $data['users'][] = [
                'name' => "User{$i}",
                'email' => "user{$i}@example.com",
                'age' => 20 + $i,
                'address' => [
                    'city' => 'Beijing',
                    'street' => 'Street' . $i,
                ],
                'tags' => ['tag1', 'tag2', 'tag3'],
            ];
        }

        $container = $this->createContainerWithRequest([], $data);

        $aspect = new ValidationAspect(
            $container,
            $container->get(\Hyperf\Validation\Contract\ValidatorFactoryInterface::class)
        );

        $joinPoint = $this->createMockJoinPoint([
            new RequestValidation(
                rules: [
                    'users' => 'required|array|min:1|max:100',
                    'users.*.name' => 'required|string|max:50',
                    'users.*.email' => 'required|email',
                    'users.*.age' => 'required|integer|between:1,150',
                    'users.*.address' => 'required|array',
                    'users.*.address.city' => 'required|string|max:50',
                    'users.*.address.street' => 'required|string|max:100',
                    'users.*.tags' => 'nullable|array|max:10',
                    'users.*.tags.*' => 'string|max:20',
                ]
            )
        ]);

        $startTime = microtime(true);
        $aspect->process($joinPoint);
        $duration = microtime(true) - $startTime;

        // 复杂嵌套验证应该在500ms内完成
        $this->assertLessThan(0.5, $duration, '复杂嵌套验证应该在500ms内完成');
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
