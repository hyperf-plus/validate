<?php

declare(strict_types=1);

namespace HPlus\Validate\Examples;

use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\PostMapping;
use HPlus\Validate\Annotations\RequestValidation;
use HPlus\Validate\Annotations\Validation;

/**
 * 验证器性能测试示例
 * 展示完整的请求生命周期和多参数验证逻辑
 */
#[Controller(prefix: "/performance")]
class PerformanceTest
{
    #[Inject]
    protected \HPlus\Validate\Validate $validator;
    
    /**
     * 多参数复杂验证示例
     * 展示验证器的性能优化特性
     */
    #[PostMapping(path: "/complex-validation")]
    #[RequestValidation(
        rules: [
            // 基础类型验证
            'user_id' => 'required|integer|min:1|max:999999',
            'username' => 'required|string|between:3,20|regex:/^[a-zA-Z0-9_]+$/',
            'email' => 'required|email|max:255',
            'mobile' => 'required|mobile',
            'age' => 'required|integer|between:18,120',
            
            // 数组验证
            'tags' => 'required|array|max:10',
            'tags.*' => 'required|string|max:20',
            
            // 嵌套对象验证
            'profile' => 'required|array',
            'profile.nickname' => 'required|string|max:30',
            'profile.avatar' => 'nullable|url|max:500',
            'profile.bio' => 'nullable|string|max:200',
            
            // 条件验证
            'type' => 'required|in:personal,business',
            'company_name' => 'required_if:type,business|string|max:100',
            'company_code' => 'required_if:type,business|regex:/^[A-Z0-9]{6,}$/',
            
            // 多字段关联验证
            'password' => 'required|string|min:8|confirmed',
            'password_confirmation' => 'required|string',
            
            // 日期时间验证
            'birth_date' => 'required|date|before:today|after:1900-01-01',
            'register_time' => 'required|date_format:Y-m-d H:i:s',
            
            // 文件验证（如果是文件上传）
            'avatar_file' => 'nullable|file|mimes:jpg,png,gif|max:2048',
            
            // 自定义规则验证
            'invite_code' => 'nullable|string|length:6|custom_rule:checkInviteCode',
        ],
        messages: [
            'username.regex' => '用户名只能包含字母、数字和下划线',
            'company_code.regex' => '公司代码必须是6位以上的大写字母和数字',
            'tags.max' => '标签最多只能有10个',
        ],
        attributes: [
            'user_id' => '用户ID',
            'username' => '用户名',
            'email' => '邮箱地址',
        ]
    )]
    public function complexValidation(array $data): array
    {
        // 记录性能指标
        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);
        
        // 这里验证已经通过，展示验证后的处理
        $result = [
            'success' => true,
            'data' => $data,
            'performance' => [
                'execution_time' => round((microtime(true) - $startTime) * 1000, 2) . 'ms',
                'memory_usage' => $this->formatBytes(memory_get_usage(true) - $startMemory),
                'peak_memory' => $this->formatBytes(memory_get_peak_usage(true)),
            ]
        ];
        
        return $result;
    }
    
    /**
     * 批量数据验证示例
     * 展示验证器的批量处理性能
     */
    #[PostMapping(path: "/batch-validation")]
    public function batchValidation(): array
    {
        $startTime = microtime(true);
        $results = [];
        
        // 模拟批量数据验证
        $batchData = $this->generateBatchData(100);
        
        // 验证规则
        $rules = [
            'id' => 'required|integer',
            'name' => 'required|string|max:50',
            'email' => 'required|email',
            'status' => 'required|in:active,inactive',
            'score' => 'required|numeric|between:0,100',
        ];
        
        // 批量验证
        foreach ($batchData as $index => $data) {
            try {
                $this->validator->validate($data, $rules);
                $results['success'][] = $index;
            } catch (\Exception $e) {
                $results['failed'][] = [
                    'index' => $index,
                    'error' => $e->getMessage()
                ];
            }
        }
        
        $executionTime = microtime(true) - $startTime;
        
        return [
            'total' => count($batchData),
            'success_count' => count($results['success'] ?? []),
            'failed_count' => count($results['failed'] ?? []),
            'execution_time' => round($executionTime * 1000, 2) . 'ms',
            'avg_time_per_validation' => round(($executionTime * 1000) / count($batchData), 4) . 'ms',
            'validations_per_second' => round(count($batchData) / $executionTime),
        ];
    }
    
    /**
     * 缓存性能测试
     * 展示规则缓存的性能提升
     */
    #[PostMapping(path: "/cache-performance")]
    public function cachePerformance(): array
    {
        $iterations = 1000;
        $rules = [
            'field1' => 'required|string|min:5|max:100',
            'field2' => 'required|integer|between:1,1000',
            'field3' => 'required|email',
            'field4' => 'required|array|min:1|max:10',
            'field5' => 'required|date|after:2020-01-01',
        ];
        
        $testData = [
            'field1' => 'test string value',
            'field2' => 500,
            'field3' => 'test@example.com',
            'field4' => [1, 2, 3, 4, 5],
            'field5' => '2023-06-15',
        ];
        
        // 第一次运行（冷启动）
        $coldStart = microtime(true);
        for ($i = 0; $i < 10; $i++) {
            $this->validator->validate($testData, $rules);
        }
        $coldTime = microtime(true) - $coldStart;
        
        // 预热缓存
        for ($i = 0; $i < 100; $i++) {
            $this->validator->validate($testData, $rules);
        }
        
        // 缓存命中测试
        $warmStart = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            $this->validator->validate($testData, $rules);
        }
        $warmTime = microtime(true) - $warmStart;
        
        return [
            'cold_start' => [
                'iterations' => 10,
                'total_time' => round($coldTime * 1000, 2) . 'ms',
                'avg_time' => round(($coldTime * 1000) / 10, 4) . 'ms',
            ],
            'warm_cache' => [
                'iterations' => $iterations,
                'total_time' => round($warmTime * 1000, 2) . 'ms',
                'avg_time' => round(($warmTime * 1000) / $iterations, 4) . 'ms',
            ],
            'performance_gain' => round((($coldTime / 10) / ($warmTime / $iterations)) * 100 - 100, 2) . '%',
            'cache_info' => [
                'rule_cache_enabled' => true,
                'validator_pool_enabled' => true,
                'reflection_cache_enabled' => true,
            ]
        ];
    }
    
    /**
     * 并发验证测试
     * 展示验证器的并发处理能力
     */
    #[PostMapping(path: "/concurrent-validation")]
    public function concurrentValidation(): array
    {
        $concurrentRequests = 50;
        $results = [];
        
        // 使用协程并发验证
        $channel = new \Swoole\Coroutine\Channel($concurrentRequests);
        
        $startTime = microtime(true);
        
        for ($i = 0; $i < $concurrentRequests; $i++) {
            go(function () use ($i, $channel) {
                $data = [
                    'id' => $i,
                    'name' => 'User ' . $i,
                    'email' => 'user' . $i . '@example.com',
                    'age' => rand(18, 65),
                ];
                
                $rules = [
                    'id' => 'required|integer',
                    'name' => 'required|string',
                    'email' => 'required|email',
                    'age' => 'required|integer|min:18',
                ];
                
                $validationStart = microtime(true);
                
                try {
                    $this->validator->validate($data, $rules);
                    $success = true;
                    $error = null;
                } catch (\Exception $e) {
                    $success = false;
                    $error = $e->getMessage();
                }
                
                $channel->push([
                    'id' => $i,
                    'success' => $success,
                    'error' => $error,
                    'time' => microtime(true) - $validationStart,
                ]);
            });
        }
        
        // 收集结果
        for ($i = 0; $i < $concurrentRequests; $i++) {
            $results[] = $channel->pop();
        }
        
        $totalTime = microtime(true) - $startTime;
        
        // 统计
        $successCount = count(array_filter($results, fn($r) => $r['success']));
        $avgTime = array_sum(array_column($results, 'time')) / count($results);
        
        return [
            'concurrent_requests' => $concurrentRequests,
            'total_time' => round($totalTime * 1000, 2) . 'ms',
            'success_count' => $successCount,
            'failed_count' => $concurrentRequests - $successCount,
            'avg_validation_time' => round($avgTime * 1000, 4) . 'ms',
            'throughput' => round($concurrentRequests / $totalTime) . ' req/s',
        ];
    }
    
    /**
     * 内存使用分析
     * 展示验证器的内存效率
     */
    #[PostMapping(path: "/memory-analysis")]
    public function memoryAnalysis(): array
    {
        $iterations = 1000;
        $memorySnapshots = [];
        
        // 基准内存
        $baseMemory = memory_get_usage(true);
        $memorySnapshots['base'] = $this->formatBytes($baseMemory);
        
        // 创建大量验证规则
        $rules = [];
        for ($i = 0; $i < 50; $i++) {
            $rules['field_' . $i] = 'required|string|min:5|max:100|regex:/^[a-zA-Z0-9]+$/';
        }
        
        $afterRulesMemory = memory_get_usage(true);
        $memorySnapshots['after_rules'] = $this->formatBytes($afterRulesMemory);
        
        // 执行验证
        $data = [];
        for ($i = 0; $i < 50; $i++) {
            $data['field_' . $i] = 'testvalue' . $i;
        }
        
        for ($i = 0; $i < $iterations; $i++) {
            $this->validator->validate($data, $rules);
            
            // 每100次记录一次内存
            if ($i % 100 === 0) {
                $memorySnapshots['iteration_' . $i] = $this->formatBytes(memory_get_usage(true));
            }
        }
        
        $finalMemory = memory_get_usage(true);
        $peakMemory = memory_get_peak_usage(true);
        
        return [
            'iterations' => $iterations,
            'memory_snapshots' => $memorySnapshots,
            'memory_growth' => $this->formatBytes($finalMemory - $baseMemory),
            'peak_memory' => $this->formatBytes($peakMemory),
            'avg_memory_per_validation' => $this->formatBytes(($finalMemory - $baseMemory) / $iterations),
            'memory_efficiency' => [
                'validator_pooling' => 'enabled',
                'rule_caching' => 'enabled',
                'object_recycling' => 'enabled',
            ]
        ];
    }
    
    /**
     * 生成批量测试数据
     */
    private function generateBatchData(int $count): array
    {
        $data = [];
        for ($i = 0; $i < $count; $i++) {
            $data[] = [
                'id' => $i + 1,
                'name' => 'User ' . ($i + 1),
                'email' => 'user' . ($i + 1) . '@example.com',
                'status' => $i % 2 === 0 ? 'active' : 'inactive',
                'score' => rand(0, 100),
            ];
        }
        return $data;
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