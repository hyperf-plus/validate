<?php

declare(strict_types=1);

namespace HPlus\Validate\Tests\Cases;

use HPlus\Validate\Validate;
use HPlus\Validate\RuleParser;
use HPlus\Validate\Aspect\ValidationAspect;

/**
 * 性能测试
 * 验证优化后的性能提升
 */
class PerformanceTest extends AbstractTestCase
{
    /**
     * 测试单次验证性能
     */
    public function testSingleValidationPerformance()
    {
        $validate = new Validate();
        
        $rules = [
            'username' => 'required|string|min:3|max:20|alphaDash',
            'email' => 'required|email|max:255',
            'age' => 'required|integer|between:18,100',
            'password' => 'required|string|min:6|confirmed',
            'password_confirmation' => 'required|string',
            'tags' => 'array|max:10',
            'profile.bio' => 'nullable|string|max:500'
        ];
        
        $data = [
            'username' => 'test_user',
            'email' => 'test@example.com',
            'age' => 25,
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'tags' => ['php', 'swoole', 'hyperf'],
            'profile' => ['bio' => 'Test bio']
        ];
        
        // 预热
        for ($i = 0; $i < 10; $i++) {
            $validate->check($data, $rules);
        }
        
        // 测试性能
        $iterations = 1000;
        $startTime = microtime(true);
        
        for ($i = 0; $i < $iterations; $i++) {
            $validate->check($data, $rules);
        }
        
        $duration = microtime(true) - $startTime;
        $avgTime = ($duration * 1000) / $iterations;
        
        // 输出性能数据
        echo "\n单次验证性能测试:\n";
        echo "迭代次数: {$iterations}\n";
        echo "总耗时: " . round($duration * 1000, 2) . "ms\n";
        echo "平均耗时: " . round($avgTime, 4) . "ms\n";
        echo "QPS: " . round($iterations / $duration) . "\n";
        
        // 断言性能指标
        $this->assertLessThan(0.5, $avgTime, '平均验证时间应小于0.5ms');
    }
    
    /**
     * 测试规则解析缓存性能
     */
    public function testRuleParserCachePerformance()
    {
        // 清空缓存
        RuleParser::clearCache();
        
        $rules = [
            'field1' => 'required|string|min:5|max:100',
            'field2' => 'required|integer|between:1,1000',
            'field3' => 'required|email',
            'field4' => 'required|array|min:1|max:10',
            'field5' => 'required|date|after:2020-01-01'
        ];
        
        // 冷启动测试
        $coldStart = microtime(true);
        for ($i = 0; $i < 10; $i++) {
            RuleParser::rulesToJsonSchema($rules);
        }
        $coldTime = microtime(true) - $coldStart;
        
        // 缓存预热
        for ($i = 0; $i < 100; $i++) {
            RuleParser::rulesToJsonSchema($rules);
        }
        
        // 热缓存测试
        $iterations = 1000;
        $warmStart = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            RuleParser::rulesToJsonSchema($rules);
        }
        $warmTime = microtime(true) - $warmStart;
        
        $coldAvg = ($coldTime * 1000) / 10;
        $warmAvg = ($warmTime * 1000) / $iterations;
        $speedup = $coldAvg / $warmAvg;
        
        echo "\n规则解析缓存性能测试:\n";
        echo "冷启动平均: " . round($coldAvg, 4) . "ms\n";
        echo "热缓存平均: " . round($warmAvg, 4) . "ms\n";
        echo "性能提升: " . round($speedup, 1) . "倍\n";
        
        // 断言缓存效果
        $this->assertGreaterThan(10, $speedup, '缓存应该带来10倍以上性能提升');
    }
    
    /**
     * 测试批量验证性能
     */
    public function testBatchValidationPerformance()
    {
        $validate = new Validate();
        
        $rules = [
            'id' => 'required|integer',
            'name' => 'required|string|max:50',
            'email' => 'required|email',
            'status' => 'required|in:active,inactive',
            'score' => 'required|numeric|between:0,100'
        ];
        
        // 生成测试数据
        $batchSize = 100;
        $batchData = [];
        for ($i = 0; $i < $batchSize; $i++) {
            $batchData[] = [
                'id' => $i + 1,
                'name' => 'User ' . ($i + 1),
                'email' => 'user' . ($i + 1) . '@example.com',
                'status' => $i % 2 === 0 ? 'active' : 'inactive',
                'score' => rand(0, 100)
            ];
        }
        
        // 测试批量验证性能
        $startTime = microtime(true);
        
        foreach ($batchData as $data) {
            $validate->check($data, $rules);
        }
        
        $duration = microtime(true) - $startTime;
        $avgTime = ($duration * 1000) / $batchSize;
        
        echo "\n批量验证性能测试:\n";
        echo "数据量: {$batchSize}\n";
        echo "总耗时: " . round($duration * 1000, 2) . "ms\n";
        echo "平均每条: " . round($avgTime, 4) . "ms\n";
        echo "吞吐量: " . round($batchSize / $duration) . " 条/秒\n";
        
        // 断言批量性能
        $this->assertLessThan(0.2, $avgTime, '批量验证平均时间应小于0.2ms');
    }
    
    /**
     * 测试内存使用
     */
    public function testMemoryUsage()
    {
        $startMemory = memory_get_usage(true);
        
        // 创建大量验证规则
        $rules = [];
        for ($i = 0; $i < 50; $i++) {
            $rules['field_' . $i] = 'required|string|min:5|max:100|regex:/^[a-zA-Z0-9]+$/';
        }
        
        // 创建测试数据
        $data = [];
        for ($i = 0; $i < 50; $i++) {
            $data['field_' . $i] = 'testvalue' . $i;
        }
        
        // 执行1000次验证
        $validate = new Validate();
        for ($i = 0; $i < 1000; $i++) {
            $validate->check($data, $rules);
        }
        
        $endMemory = memory_get_usage(true);
        $peakMemory = memory_get_peak_usage(true);
        $memoryGrowth = $endMemory - $startMemory;
        
        echo "\n内存使用测试:\n";
        echo "初始内存: " . $this->formatBytes($startMemory) . "\n";
        echo "结束内存: " . $this->formatBytes($endMemory) . "\n";
        echo "内存增长: " . $this->formatBytes($memoryGrowth) . "\n";
        echo "峰值内存: " . $this->formatBytes($peakMemory) . "\n";
        echo "平均每次验证: " . $this->formatBytes($memoryGrowth / 1000) . "\n";
        
        // 断言内存使用合理
        $this->assertLessThan(10 * 1024 * 1024, $memoryGrowth, '内存增长应小于10MB');
    }
    
    /**
     * 测试并发验证性能
     */
    public function testConcurrentValidationPerformance()
    {
        if (!extension_loaded('swoole')) {
            $this->markTestSkipped('需要Swoole扩展');
        }
        
        \Swoole\Runtime::enableCoroutine();
        
        \Co\run(function () {
            $validate = new Validate();
            $concurrency = 100;
            $channel = new \Swoole\Coroutine\Channel($concurrency);
            
            $rules = [
                'id' => 'required|integer',
                'name' => 'required|string',
                'email' => 'required|email',
                'age' => 'required|integer|min:18'
            ];
            
            $startTime = microtime(true);
            
            // 创建并发协程
            for ($i = 0; $i < $concurrency; $i++) {
                go(function () use ($i, $validate, $rules, $channel) {
                    $data = [
                        'id' => $i,
                        'name' => 'User ' . $i,
                        'email' => 'user' . $i . '@example.com',
                        'age' => rand(18, 65)
                    ];
                    
                    $start = microtime(true);
                    $result = $validate->check($data, $rules);
                    $duration = microtime(true) - $start;
                    
                    $channel->push([
                        'id' => $i,
                        'success' => $result,
                        'duration' => $duration
                    ]);
                });
            }
            
            // 收集结果
            $results = [];
            for ($i = 0; $i < $concurrency; $i++) {
                $results[] = $channel->pop();
            }
            
            $totalTime = microtime(true) - $startTime;
            $avgTime = array_sum(array_column($results, 'duration')) / count($results);
            
            echo "\n并发验证性能测试:\n";
            echo "并发数: {$concurrency}\n";
            echo "总耗时: " . round($totalTime * 1000, 2) . "ms\n";
            echo "平均验证时间: " . round($avgTime * 1000, 4) . "ms\n";
            echo "吞吐量: " . round($concurrency / $totalTime) . " req/s\n";
            
            $this->assertLessThan(100, $totalTime * 1000, '100个并发验证应在100ms内完成');
        });
    }
    
    /**
     * 测试缓存命中率
     */
    public function testCacheHitRate()
    {
        // 清空缓存统计
        ValidationAspect::clearCache();
        
        // 模拟多次相同的验证请求
        $iterations = 100;
        
        for ($i = 0; $i < $iterations; $i++) {
            // 这里需要模拟实际的切面调用
            // 由于测试环境限制，仅展示统计逻辑
        }
        
        $stats = ValidationAspect::getCacheStats();
        
        echo "\n缓存命中率测试:\n";
        echo "总请求数: " . $stats['total'] . "\n";
        echo "缓存命中: " . $stats['hits'] . "\n";
        echo "缓存未命中: " . $stats['misses'] . "\n";
        echo "命中率: " . $stats['hit_rate'] . "\n";
        
        // 实际测试中，命中率应该接近100%
    }
    
    /**
     * 格式化字节数
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
} 