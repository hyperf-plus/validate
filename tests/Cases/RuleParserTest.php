<?php

declare(strict_types=1);

namespace HPlus\Validate\Tests\Cases;

use HPlus\Validate\RuleParser;

/**
 * 规则解析器测试
 * @covers \HPlus\Validate\RuleParser
 */
class RuleParserTest extends AbstractTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // 清空缓存
        RuleParser::clearCache();
    }
    
    /**
     * 测试字段名解析
     */
    public function testParseFieldName()
    {
        // 测试普通字段名
        $result = RuleParser::parseFieldName('username');
        $this->assertEquals(['username', ''], $result);
        
        // 测试带描述的字段名
        $result = RuleParser::parseFieldName('username|用户名');
        $this->assertEquals(['username', '用户名'], $result);
        
        // 测试带特殊字符的字段名
        $result = RuleParser::parseFieldName('user_name|用户名称');
        $this->assertEquals(['user_name', '用户名称'], $result);
        
        // 测试缓存功能
        $result1 = RuleParser::parseFieldName('test|测试');
        $result2 = RuleParser::parseFieldName('test|测试');
        $this->assertSame($result1, $result2); // 应该是同一个缓存结果
    }
    
    /**
     * 测试规则转JSON Schema
     */
    public function testRuleToJsonSchema()
    {
        // 测试基础类型
        $schema = RuleParser::ruleToJsonSchema('required|string|max:50');
        $this->assertEquals('string', $schema['type']);
        $this->assertEquals(50, $schema['maxLength']);
        
        // 测试整数类型
        $schema = RuleParser::ruleToJsonSchema('integer|min:1|max:100');
        $this->assertEquals('integer', $schema['type']);
        $this->assertEquals(1, $schema['minimum']);
        $this->assertEquals(100, $schema['maximum']);
        
        // 测试数组类型
        $schema = RuleParser::ruleToJsonSchema('array|min:1|max:10');
        $this->assertEquals('array', $schema['type']);
        $this->assertEquals(1, $schema['minItems']);
        $this->assertEquals(10, $schema['maxItems']);
        
        // 测试布尔类型
        $schema = RuleParser::ruleToJsonSchema('boolean');
        $this->assertEquals('boolean', $schema['type']);
        
        // 测试可空
        $schema = RuleParser::ruleToJsonSchema('nullable|string');
        $this->assertEquals('string', $schema['type']);
        $this->assertTrue($schema['nullable']);
    }
    
    /**
     * 测试格式检测
     */
    public function testFormatDetection()
    {
        // 测试email格式
        $schema = RuleParser::ruleToJsonSchema('email');
        $this->assertEquals('email', $schema['format']);
        
        // 测试url格式
        $schema = RuleParser::ruleToJsonSchema('url');
        $this->assertEquals('uri', $schema['format']);
        
        // 测试日期格式
        $schema = RuleParser::ruleToJsonSchema('date');
        $this->assertEquals('date', $schema['format']);
        
        // 测试日期时间格式
        $schema = RuleParser::ruleToJsonSchema('date_format:Y-m-d H:i:s');
        $this->assertEquals('date-time', $schema['format']);
        
        // 测试IP格式
        $schema = RuleParser::ruleToJsonSchema('ip');
        $this->assertEquals('ipv4', $schema['format']);
    }
    
    /**
     * 测试约束条件
     */
    public function testConstraints()
    {
        // 测试between
        $schema = RuleParser::ruleToJsonSchema('integer|between:18,65');
        $this->assertEquals(18, $schema['minimum']);
        $this->assertEquals(65, $schema['maximum']);
        
        // 测试in（枚举）
        $schema = RuleParser::ruleToJsonSchema('in:active,inactive,pending');
        $this->assertEquals(['active', 'inactive', 'pending'], $schema['enum']);
        
        // 测试正则
        $schema = RuleParser::ruleToJsonSchema('regex:/^[A-Z]+$/');
        $this->assertEquals('/^[A-Z]+$/', $schema['pattern']);
        
        // 测试size
        $schema = RuleParser::ruleToJsonSchema('string|size:6');
        $this->assertEquals(6, $schema['minLength']);
        $this->assertEquals(6, $schema['maxLength']);
    }
    
    /**
     * 测试规则数组转JSON Schema
     */
    public function testRulesToJsonSchema()
    {
        $rules = [
            'username' => 'required|string|min:3|max:20',
            'email' => 'required|email',
            'age' => 'integer|between:18,100',
            'tags' => 'array|max:5'
        ];
        
        $schema = RuleParser::rulesToJsonSchema($rules);
        
        // 验证基本结构
        $this->assertEquals('object', $schema['type']);
        $this->assertArrayHasKey('properties', $schema);
        $this->assertArrayHasKey('required', $schema);
        
        // 验证必填字段
        $this->assertContains('username', $schema['required']);
        $this->assertContains('email', $schema['required']);
        $this->assertNotContains('age', $schema['required']); // age不是required
        
        // 验证属性定义
        $this->assertEquals('string', $schema['properties']['username']['type']);
        $this->assertEquals(3, $schema['properties']['username']['minLength']);
        $this->assertEquals(20, $schema['properties']['username']['maxLength']);
        
        $this->assertEquals('string', $schema['properties']['email']['type']);
        $this->assertEquals('email', $schema['properties']['email']['format']);
        
        $this->assertEquals('integer', $schema['properties']['age']['type']);
        $this->assertEquals(18, $schema['properties']['age']['minimum']);
        $this->assertEquals(100, $schema['properties']['age']['maximum']);
        
        $this->assertEquals('array', $schema['properties']['tags']['type']);
        $this->assertEquals(5, $schema['properties']['tags']['maxItems']);
    }
    
    /**
     * 测试带描述的字段
     */
    public function testFieldWithDescription()
    {
        $rules = [
            'username|用户名' => 'required|string',
            'email|邮箱地址' => 'required|email'
        ];
        
        $schema = RuleParser::rulesToJsonSchema($rules);
        
        // 验证字段名正确解析
        $this->assertArrayHasKey('username', $schema['properties']);
        $this->assertArrayHasKey('email', $schema['properties']);
        
        // 验证描述被添加
        $this->assertEquals('用户名', $schema['properties']['username']['description']);
        $this->assertEquals('邮箱地址', $schema['properties']['email']['description']);
    }
    
    /**
     * 测试缓存功能
     */
    public function testCaching()
    {
        // 获取初始缓存统计
        $stats = RuleParser::getCacheStats();
        $initialRuleCache = $stats['rule_cache_size'];
        
        // 第一次解析
        $schema1 = RuleParser::ruleToJsonSchema('required|string|max:50');
        
        // 检查缓存增加
        $stats = RuleParser::getCacheStats();
        $this->assertEquals($initialRuleCache + 1, $stats['rule_cache_size']);
        
        // 第二次解析相同规则（应该从缓存获取）
        $schema2 = RuleParser::ruleToJsonSchema('required|string|max:50');
        
        // 缓存数量不应该增加
        $stats = RuleParser::getCacheStats();
        $this->assertEquals($initialRuleCache + 1, $stats['rule_cache_size']);
        
        // 结果应该相同
        $this->assertEquals($schema1, $schema2);
    }
    
    /**
     * 测试批量操作
     */
    public function testBatchOperations()
    {
        // 测试批量解析字段名
        $fields = [
            'name|姓名',
            'email|邮箱',
            'age|年龄'
        ];
        
        $results = RuleParser::batchParseFieldNames($fields);
        
        $this->assertEquals(['name', '姓名'], $results['name|姓名']);
        $this->assertEquals(['email', '邮箱'], $results['email|邮箱']);
        $this->assertEquals(['age', '年龄'], $results['age|年龄']);
        
        // 测试批量转换规则
        $rules = [
            'required|string',
            'required|integer',
            'required|email'
        ];
        
        $results = RuleParser::batchRuleToJsonSchema($rules);
        
        $this->assertEquals('string', $results['required|string']['type']);
        $this->assertEquals('integer', $results['required|integer']['type']);
        $this->assertEquals('email', $results['required|email']['format']);
    }
    
    /**
     * 测试默认值和示例值
     */
    public function testDefaultAndExampleValues()
    {
        // 测试默认值
        $default = RuleParser::getDefaultValue('string|default:test');
        $this->assertEquals('test', $default);
        
        $default = RuleParser::getDefaultValue('integer|default:10');
        $this->assertSame(10, $default);
        
        $default = RuleParser::getDefaultValue('boolean|default:true');
        $this->assertTrue($default);
        
        $default = RuleParser::getDefaultValue('array|default:1,2,3');
        $this->assertEquals(['1', '2', '3'], $default);
        
        // 测试示例值
        $example = RuleParser::getExampleValue('email|example:user@example.com');
        $this->assertEquals('user@example.com', $example);
        
        // 测试自动生成的示例值
        $example = RuleParser::getExampleValue('email');
        $this->assertEquals('user@example.com', $example);
        
        $example = RuleParser::getExampleValue('url');
        $this->assertEquals('https://example.com', $example);
        
        $example = RuleParser::getExampleValue('uuid');
        $this->assertEquals('123e4567-e89b-12d3-a456-426614174000', $example);
    }
    
    /**
     * 测试内存优化
     */
    public function testMemoryOptimization()
    {
        // 添加大量缓存
        for ($i = 0; $i < 2000; $i++) {
            RuleParser::ruleToJsonSchema("required|string|max:{$i}");
        }
        
        // 获取缓存统计
        $stats = RuleParser::getCacheStats();
        $beforeOptimize = $stats['rule_cache_size'];
        
        // 执行内存优化
        RuleParser::optimizeMemory();
        
        // 再次获取统计
        $stats = RuleParser::getCacheStats();
        $afterOptimize = $stats['rule_cache_size'];
        
        // 缓存应该被限制在1000以内
        $this->assertLessThanOrEqual(1000, $afterOptimize);
        $this->assertLessThan($beforeOptimize, $afterOptimize);
    }
    
    /**
     * 测试预热功能
     */
    public function testWarmupCache()
    {
        // 清空缓存
        RuleParser::clearCache();
        
        $stats = RuleParser::getCacheStats();
        $this->assertEquals(0, $stats['rule_cache_size']);
        
        // 执行预热
        RuleParser::warmupCache();
        
        // 检查常用规则是否被缓存
        $stats = RuleParser::getCacheStats();
        $this->assertGreaterThan(0, $stats['rule_cache_size']);
        
        // 验证常用规则可以直接使用
        $schema = RuleParser::ruleToJsonSchema('required|string|max:255');
        $this->assertNotEmpty($schema);
    }
} 