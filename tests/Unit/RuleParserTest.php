<?php

declare(strict_types=1);

namespace HPlus\Validate\Tests\Unit;

use HPlus\Validate\RuleParser;
use HPlus\Validate\Tests\TestCase;

/**
 * RuleParser 单元测试
 */
class RuleParserTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        RuleParser::clearCache();
    }

    protected function tearDown(): void
    {
        RuleParser::clearCache();
        parent::tearDown();
    }

    /**
     * 测试解析字段名
     */
    public function testParseFieldName(): void
    {
        [$field, $description] = RuleParser::parseFieldName('email|用户邮箱');
        $this->assertEquals('email', $field);
        $this->assertEquals('用户邮箱', $description);

        [$field2, $description2] = RuleParser::parseFieldName('name');
        $this->assertEquals('name', $field2);
        $this->assertEquals('', $description2);
    }

    /**
     * 测试规则转 JSON Schema - 字符串类型
     */
    public function testRuleToJsonSchemaString(): void
    {
        $schema = RuleParser::ruleToJsonSchema('required|string|max:50');
        
        $this->assertEquals('string', $schema['type']);
        $this->assertEquals(50, $schema['maxLength']);
    }

    /**
     * 测试规则转 JSON Schema - 整数类型
     */
    public function testRuleToJsonSchemaInteger(): void
    {
        $schema = RuleParser::ruleToJsonSchema('required|integer|between:1,100');
        
        $this->assertEquals('integer', $schema['type']);
        $this->assertEquals(1, $schema['minimum']);
        $this->assertEquals(100, $schema['maximum']);
    }

    /**
     * 测试规则转 JSON Schema - 数字类型
     */
    public function testRuleToJsonSchemaNumeric(): void
    {
        $schema = RuleParser::ruleToJsonSchema('numeric|min:0|max:999');
        
        $this->assertEquals('number', $schema['type']);
        $this->assertEquals(0, $schema['minimum']);
        $this->assertEquals(999, $schema['maximum']);
    }

    /**
     * 测试规则转 JSON Schema - 邮箱格式
     */
    public function testRuleToJsonSchemaEmail(): void
    {
        $schema = RuleParser::ruleToJsonSchema('required|email');
        
        $this->assertEquals('string', $schema['type']);
        $this->assertEquals('email', $schema['format']);
    }

    /**
     * 测试规则转 JSON Schema - URL 格式
     */
    public function testRuleToJsonSchemaUrl(): void
    {
        $schema = RuleParser::ruleToJsonSchema('url');
        
        $this->assertEquals('string', $schema['type']);
        $this->assertEquals('uri', $schema['format']);
    }

    /**
     * 测试规则转 JSON Schema - 布尔类型
     */
    public function testRuleToJsonSchemaBoolean(): void
    {
        $schema = RuleParser::ruleToJsonSchema('boolean');
        
        $this->assertEquals('boolean', $schema['type']);
    }

    /**
     * 测试规则转 JSON Schema - 数组类型
     */
    public function testRuleToJsonSchemaArray(): void
    {
        $schema = RuleParser::ruleToJsonSchema('array|min:1|max:10');
        
        $this->assertEquals('array', $schema['type']);
        $this->assertEquals(1, $schema['minItems']);
        $this->assertEquals(10, $schema['maxItems']);
    }

    /**
     * 测试规则转 JSON Schema - 枚举
     */
    public function testRuleToJsonSchemaEnum(): void
    {
        $schema = RuleParser::ruleToJsonSchema('in:pending,approved,rejected');
        
        $this->assertArrayHasKey('enum', $schema);
        $this->assertEquals(['pending', 'approved', 'rejected'], $schema['enum']);
    }

    /**
     * 测试规则转 JSON Schema - 正则表达式
     */
    public function testRuleToJsonSchemaRegex(): void
    {
        $schema = RuleParser::ruleToJsonSchema('regex:/^[A-Z][a-z]+$/');
        
        $this->assertArrayHasKey('pattern', $schema);
        $this->assertEquals('/^[A-Z][a-z]+$/', $schema['pattern']);
    }

    /**
     * 测试规则转 JSON Schema - 可空
     */
    public function testRuleToJsonSchemaNullable(): void
    {
        $schema = RuleParser::ruleToJsonSchema('nullable|string');
        
        $this->assertTrue($schema['nullable']);
    }

    /**
     * 测试规则转 JSON Schema - 固定大小
     */
    public function testRuleToJsonSchemaSize(): void
    {
        $schema = RuleParser::ruleToJsonSchema('string|size:10');
        
        $this->assertEquals(10, $schema['minLength']);
        $this->assertEquals(10, $schema['maxLength']);
    }

    /**
     * 测试批量规则转 JSON Schema
     */
    public function testRulesToJsonSchema(): void
    {
        $schema = RuleParser::rulesToJsonSchema([
            'name|姓名' => 'required|string|max:50',
            'age|年龄' => 'nullable|integer|between:18,100',
            'email|邮箱' => 'required|email',
        ]);

        $this->assertEquals('object', $schema['type']);
        $this->assertArrayHasKey('properties', $schema);
        $this->assertArrayHasKey('required', $schema);

        // 检查属性
        $this->assertArrayHasKey('name', $schema['properties']);
        $this->assertArrayHasKey('age', $schema['properties']);
        $this->assertArrayHasKey('email', $schema['properties']);

        // 检查描述
        $this->assertEquals('姓名', $schema['properties']['name']['description']);
        $this->assertEquals('年龄', $schema['properties']['age']['description']);
        $this->assertEquals('邮箱', $schema['properties']['email']['description']);

        // 检查必填字段
        $this->assertContains('name', $schema['required']);
        $this->assertContains('email', $schema['required']);
        $this->assertNotContains('age', $schema['required']);
    }

    /**
     * 测试 isRequired
     */
    public function testIsRequired(): void
    {
        $this->assertTrue(RuleParser::isRequired('required|string'));
        $this->assertTrue(RuleParser::isRequired('string|required'));
        $this->assertFalse(RuleParser::isRequired('nullable|string'));
        $this->assertFalse(RuleParser::isRequired('string'));
    }

    /**
     * 测试 isNullable
     */
    public function testIsNullable(): void
    {
        $this->assertTrue(RuleParser::isNullable('nullable|string'));
        $this->assertTrue(RuleParser::isNullable('string|nullable'));
        $this->assertFalse(RuleParser::isNullable('required|string'));
        $this->assertFalse(RuleParser::isNullable('string'));
    }

    /**
     * 测试 isArray
     */
    public function testIsArray(): void
    {
        $this->assertTrue(RuleParser::isArray('array'));
        $this->assertTrue(RuleParser::isArray('required|array'));
        $this->assertFalse(RuleParser::isArray('string'));
    }

    /**
     * 测试 isFile
     */
    public function testIsFile(): void
    {
        $this->assertTrue(RuleParser::isFile('file'));
        $this->assertTrue(RuleParser::isFile('image'));
        $this->assertTrue(RuleParser::isFile('required|file'));
        $this->assertFalse(RuleParser::isFile('string'));
    }

    /**
     * 测试缓存功能
     */
    public function testRuleCache(): void
    {
        $rule = 'required|string|max:50';

        // 第一次调用
        $schema1 = RuleParser::ruleToJsonSchema($rule);
        
        // 第二次调用应该从缓存获取
        $schema2 = RuleParser::ruleToJsonSchema($rule);
        
        // 应该返回相同的结果
        $this->assertEquals($schema1, $schema2);

        // 检查缓存统计
        $stats = RuleParser::getCacheStats();
        $this->assertGreaterThan(0, $stats['rule_cache_size']);
    }

    /**
     * 测试清空缓存
     */
    public function testClearCache(): void
    {
        RuleParser::ruleToJsonSchema('required|string');
        
        $statsBefore = RuleParser::getCacheStats();
        $this->assertGreaterThan(0, $statsBefore['rule_cache_size']);

        RuleParser::clearCache();

        $statsAfter = RuleParser::getCacheStats();
        $this->assertEquals(0, $statsAfter['rule_cache_size']);
        $this->assertEquals(0, $statsAfter['field_cache_size']);
        $this->assertEquals(0, $statsAfter['schema_cache_size']);
    }

    /**
     * 测试批量解析字段名
     */
    public function testBatchParseFieldNames(): void
    {
        $fields = [
            'name|姓名',
            'email|邮箱',
            'age',
        ];

        $results = RuleParser::batchParseFieldNames($fields);

        $this->assertCount(3, $results);
        $this->assertEquals(['name', '姓名'], $results['name|姓名']);
        $this->assertEquals(['email', '邮箱'], $results['email|邮箱']);
        $this->assertEquals(['age', ''], $results['age']);
    }

    /**
     * 测试获取默认值
     */
    public function testGetDefaultValue(): void
    {
        $this->assertEquals('test', RuleParser::getDefaultValue('string|default:test'));
        $this->assertEquals(10, RuleParser::getDefaultValue('integer|default:10'));
        $this->assertEquals(10.5, RuleParser::getDefaultValue('numeric|default:10.5'));
        $this->assertTrue(RuleParser::getDefaultValue('boolean|default:true'));
        $this->assertEquals(['a', 'b'], RuleParser::getDefaultValue('array|default:a,b'));
        $this->assertNull(RuleParser::getDefaultValue('string'));
    }

    /**
     * 测试获取示例值
     */
    public function testGetExampleValue(): void
    {
        $this->assertEquals('test', RuleParser::getExampleValue('string|example:test'));
        $this->assertEquals('user@example.com', RuleParser::getExampleValue('email'));
        $this->assertEquals('https://example.com', RuleParser::getExampleValue('url'));
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            RuleParser::getExampleValue('uuid')
        );
        $this->assertEquals('2023-01-01', RuleParser::getExampleValue('date'));
    }

    /**
     * 测试复杂规则组合
     */
    public function testComplexRules(): void
    {
        $schema = RuleParser::ruleToJsonSchema(
            'required|string|min:3|max:50|regex:/^[A-Za-z0-9_]+$/'
        );

        $this->assertEquals('string', $schema['type']);
        $this->assertEquals(3, $schema['minLength']);
        $this->assertEquals(50, $schema['maxLength']);
        $this->assertEquals('/^[A-Za-z0-9_]+$/', $schema['pattern']);
    }

    /**
     * 测试预热缓存
     */
    public function testWarmupCache(): void
    {
        RuleParser::clearCache();
        
        $statsBefore = RuleParser::getCacheStats();
        $this->assertEquals(0, $statsBefore['rule_cache_size']);

        RuleParser::warmupCache();

        $statsAfter = RuleParser::getCacheStats();
        $this->assertGreaterThan(0, $statsAfter['rule_cache_size']);
    }

    /**
     * 测试内存优化
     */
    public function testOptimizeMemory(): void
    {
        // 生成大量缓存
        for ($i = 0; $i < 1500; $i++) {
            RuleParser::ruleToJsonSchema("string|max:{$i}");
        }

        $statsBefore = RuleParser::getCacheStats();
        $sizeBefore = $statsBefore['rule_cache_size'];

        RuleParser::optimizeMemory();

        $statsAfter = RuleParser::getCacheStats();
        $sizeAfter = $statsAfter['rule_cache_size'];

        // 缓存应该被限制在阈值内
        $this->assertLessThanOrEqual(1000, $sizeAfter);
    }
}
