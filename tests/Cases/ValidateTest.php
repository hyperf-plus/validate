<?php

declare(strict_types=1);

namespace HPlus\Validate\Tests\Cases;

use HPlus\Validate\Validate;
use HPlus\Validate\Exception\ValidateException;

/**
 * 核心验证功能测试
 * @covers \HPlus\Validate\Validate
 */
class ValidateTest extends AbstractTestCase
{
    protected Validate $validate;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->validate = new Validate();
    }
    
    /**
     * 测试基础类型验证
     */
    public function testBasicTypeValidation()
    {
        // 测试 required
        $this->assertTrue($this->validate->check(['name' => 'test'], ['name' => 'required']));
        $this->assertFalse($this->validate->check(['name' => ''], ['name' => 'required']));
        $this->assertFalse($this->validate->check([], ['name' => 'required']));
        
        // 测试 string
        $this->assertTrue($this->validate->check(['name' => 'test'], ['name' => 'string']));
        $this->assertFalse($this->validate->check(['name' => 123], ['name' => 'string']));
        
        // 测试 integer
        $this->assertTrue($this->validate->check(['age' => 18], ['age' => 'integer']));
        $this->assertTrue($this->validate->check(['age' => '18'], ['age' => 'integer']));
        $this->assertFalse($this->validate->check(['age' => '18.5'], ['age' => 'integer']));
        
        // 测试 numeric
        $this->assertTrue($this->validate->check(['price' => 18.5], ['price' => 'numeric']));
        $this->assertTrue($this->validate->check(['price' => '18.5'], ['price' => 'numeric']));
        $this->assertFalse($this->validate->check(['price' => 'abc'], ['price' => 'numeric']));
        
        // 测试 boolean
        $this->assertTrue($this->validate->check(['active' => true], ['active' => 'boolean']));
        $this->assertTrue($this->validate->check(['active' => false], ['active' => 'boolean']));
        $this->assertTrue($this->validate->check(['active' => 1], ['active' => 'boolean']));
        $this->assertTrue($this->validate->check(['active' => 0], ['active' => 'boolean']));
        
        // 测试 array
        $this->assertTrue($this->validate->check(['tags' => [1, 2, 3]], ['tags' => 'array']));
        $this->assertFalse($this->validate->check(['tags' => 'string'], ['tags' => 'array']));
    }
    
    /**
     * 测试长度验证
     */
    public function testLengthValidation()
    {
        // 测试 min
        $this->assertTrue($this->validate->check(['name' => 'test'], ['name' => 'min:3']));
        $this->assertFalse($this->validate->check(['name' => 'ab'], ['name' => 'min:3']));
        
        // 测试 max
        $this->assertTrue($this->validate->check(['name' => 'test'], ['name' => 'max:10']));
        $this->assertFalse($this->validate->check(['name' => 'this is too long'], ['name' => 'max:10']));
        
        // 测试 length
        $this->assertTrue($this->validate->check(['code' => '123456'], ['code' => 'length:6']));
        $this->assertFalse($this->validate->check(['code' => '12345'], ['code' => 'length:6']));
        
        // 测试 between
        $this->assertTrue($this->validate->check(['age' => 25], ['age' => 'between:18,65']));
        $this->assertFalse($this->validate->check(['age' => 17], ['age' => 'between:18,65']));
        $this->assertFalse($this->validate->check(['age' => 66], ['age' => 'between:18,65']));
    }
    
    /**
     * 测试格式验证
     */
    public function testFormatValidation()
    {
        // 测试 email
        $this->assertTrue($this->validate->check(['email' => 'test@example.com'], ['email' => 'email']));
        $this->assertFalse($this->validate->check(['email' => 'invalid-email'], ['email' => 'email']));
        
        // 测试 url
        $this->assertTrue($this->validate->check(['url' => 'https://example.com'], ['url' => 'url']));
        $this->assertFalse($this->validate->check(['url' => 'not-a-url'], ['url' => 'url']));
        
        // 测试 ip
        $this->assertTrue($this->validate->check(['ip' => '192.168.1.1'], ['ip' => 'ip']));
        $this->assertFalse($this->validate->check(['ip' => '999.999.999.999'], ['ip' => 'ip']));
        
        // 测试 mobile
        $this->assertTrue($this->validate->check(['mobile' => '13800138000'], ['mobile' => 'mobile']));
        $this->assertFalse($this->validate->check(['mobile' => '12345678901'], ['mobile' => 'mobile']));
    }
    
    /**
     * 测试正则验证
     */
    public function testRegexValidation()
    {
        // 测试 alpha
        $this->assertTrue($this->validate->check(['name' => 'abc'], ['name' => 'alpha']));
        $this->assertFalse($this->validate->check(['name' => 'abc123'], ['name' => 'alpha']));
        
        // 测试 alphaNum
        $this->assertTrue($this->validate->check(['code' => 'abc123'], ['code' => 'alphaNum']));
        $this->assertFalse($this->validate->check(['code' => 'abc-123'], ['code' => 'alphaNum']));
        
        // 测试 alphaDash
        $this->assertTrue($this->validate->check(['slug' => 'abc-123_test'], ['slug' => 'alphaDash']));
        $this->assertFalse($this->validate->check(['slug' => 'abc 123'], ['slug' => 'alphaDash']));
        
        // 测试自定义正则
        $this->assertTrue($this->validate->check(['code' => 'ABC123'], ['code' => 'regex:/^[A-Z]+[0-9]+$/']));
        $this->assertFalse($this->validate->check(['code' => 'abc123'], ['code' => 'regex:/^[A-Z]+[0-9]+$/']));
    }
    
    /**
     * 测试比较验证
     */
    public function testComparisonValidation()
    {
        $data = ['price' => 100, 'min_price' => 50, 'max_price' => 200];
        
        // 测试 gt (greater than)
        $this->assertTrue($this->validate->check($data, ['price' => 'gt:50']));
        $this->assertFalse($this->validate->check($data, ['price' => 'gt:100']));
        
        // 测试 egt (greater than or equal)
        $this->assertTrue($this->validate->check($data, ['price' => 'egt:100']));
        $this->assertFalse($this->validate->check($data, ['price' => 'egt:101']));
        
        // 测试 lt (less than)
        $this->assertTrue($this->validate->check($data, ['price' => 'lt:200']));
        $this->assertFalse($this->validate->check($data, ['price' => 'lt:100']));
        
        // 测试 elt (less than or equal)
        $this->assertTrue($this->validate->check($data, ['price' => 'elt:100']));
        $this->assertFalse($this->validate->check($data, ['price' => 'elt:99']));
        
        // 测试 eq (equal)
        $this->assertTrue($this->validate->check($data, ['price' => 'eq:100']));
        $this->assertFalse($this->validate->check($data, ['price' => 'eq:99']));
    }
    
    /**
     * 测试范围验证
     */
    public function testRangeValidation()
    {
        // 测试 in
        $this->assertTrue($this->validate->check(['status' => 'active'], ['status' => 'in:active,inactive,pending']));
        $this->assertFalse($this->validate->check(['status' => 'deleted'], ['status' => 'in:active,inactive,pending']));
        
        // 测试 notIn
        $this->assertTrue($this->validate->check(['status' => 'active'], ['status' => 'notIn:deleted,banned']));
        $this->assertFalse($this->validate->check(['status' => 'deleted'], ['status' => 'notIn:deleted,banned']));
    }
    
    /**
     * 测试日期验证
     */
    public function testDateValidation()
    {
        // 测试 date
        $this->assertTrue($this->validate->check(['date' => '2023-12-25'], ['date' => 'date']));
        $this->assertFalse($this->validate->check(['date' => 'not-a-date'], ['date' => 'date']));
        
        // 测试 dateFormat
        $this->assertTrue($this->validate->check(['date' => '2023-12-25'], ['date' => 'dateFormat:Y-m-d']));
        $this->assertFalse($this->validate->check(['date' => '25/12/2023'], ['date' => 'dateFormat:Y-m-d']));
        
        // 测试 before
        $this->assertTrue($this->validate->check(['date' => '2023-01-01'], ['date' => 'before:2023-12-31']));
        $this->assertFalse($this->validate->check(['date' => '2024-01-01'], ['date' => 'before:2023-12-31']));
        
        // 测试 after
        $this->assertTrue($this->validate->check(['date' => '2024-01-01'], ['date' => 'after:2023-12-31']));
        $this->assertFalse($this->validate->check(['date' => '2023-01-01'], ['date' => 'after:2023-12-31']));
    }
    
    /**
     * 测试条件验证
     */
    public function testConditionalValidation()
    {
        // 测试 requireIf
        $data1 = ['type' => 'company', 'company_name' => 'Test Inc'];
        $data2 = ['type' => 'personal'];
        $data3 = ['type' => 'company']; // 缺少 company_name
        
        $rules = ['company_name' => 'requireIf:type,company'];
        
        $this->assertTrue($this->validate->check($data1, $rules));
        $this->assertTrue($this->validate->check($data2, $rules));
        $this->assertFalse($this->validate->check($data3, $rules));
        
        // 测试 requireWith
        $data1 = ['password' => '123456', 'password_confirmation' => '123456'];
        $data2 = ['username' => 'test'];
        $data3 = ['password' => '123456']; // 缺少确认字段
        
        $rules = ['password_confirmation' => 'requireWith:password'];
        
        $this->assertTrue($this->validate->check($data1, $rules));
        $this->assertTrue($this->validate->check($data2, $rules));
        $this->assertFalse($this->validate->check($data3, $rules));
    }
    
    /**
     * 测试字段比较验证
     */
    public function testFieldComparisonValidation()
    {
        // 测试 confirm/confirmed
        $data1 = ['password' => '123456', 'password_confirmation' => '123456'];
        $data2 = ['password' => '123456', 'password_confirmation' => '654321'];
        
        $this->assertTrue($this->validate->check($data1, ['password' => 'confirmed']));
        $this->assertFalse($this->validate->check($data2, ['password' => 'confirmed']));
        
        // 测试 different
        $data1 = ['username' => 'user1', 'nickname' => 'nick1'];
        $data2 = ['username' => 'same', 'nickname' => 'same'];
        
        $this->assertTrue($this->validate->check($data1, ['username' => 'different:nickname']));
        $this->assertFalse($this->validate->check($data2, ['username' => 'different:nickname']));
    }
    
    /**
     * 测试多规则组合
     */
    public function testMultipleRules()
    {
        $rules = [
            'username' => 'required|string|min:3|max:20|alphaDash',
            'email' => 'required|email',
            'age' => 'required|integer|between:18,100',
            'password' => 'required|string|min:6|confirmed',
            'password_confirmation' => 'required'
        ];
        
        $validData = [
            'username' => 'test_user',
            'email' => 'test@example.com',
            'age' => 25,
            'password' => 'password123',
            'password_confirmation' => 'password123'
        ];
        
        $this->assertTrue($this->validate->check($validData, $rules));
        
        // 测试无效数据
        $invalidData = [
            'username' => 'ab', // 太短
            'email' => 'invalid-email',
            'age' => 150, // 超出范围
            'password' => '123', // 太短
            'password_confirmation' => '456' // 不匹配
        ];
        
        $this->assertFalse($this->validate->check($invalidData, $rules));
    }
    
    /**
     * 测试嵌套数组验证
     */
    public function testNestedArrayValidation()
    {
        $rules = [
            'users' => 'required|array',
            'users.*.name' => 'required|string',
            'users.*.email' => 'required|email',
            'users.*.age' => 'integer|min:18'
        ];
        
        $validData = [
            'users' => [
                ['name' => 'User 1', 'email' => 'user1@example.com', 'age' => 25],
                ['name' => 'User 2', 'email' => 'user2@example.com', 'age' => 30]
            ]
        ];
        
        // 注意：原始的Validate类可能不支持通配符语法，这里仅作示例
        // 实际测试时需要根据具体实现调整
    }
    
    /**
     * 测试nullable规则
     */
    public function testNullableValidation()
    {
        $rules = [
            'optional_field' => 'nullable|string|min:3'
        ];
        
        // null值应该通过
        $this->assertTrue($this->validate->check(['optional_field' => null], $rules));
        
        // 空字符串不应该通过（如果有值就必须满足min:3）
        $this->assertFalse($this->validate->check(['optional_field' => ''], $rules));
        
        // 有效值应该通过
        $this->assertTrue($this->validate->check(['optional_field' => 'test'], $rules));
        
        // 无效值不应该通过
        $this->assertFalse($this->validate->check(['optional_field' => 'ab'], $rules));
    }
    
    /**
     * 测试批量验证
     */
    public function testBatchValidation()
    {
        $rules = [
            'name' => 'required',
            'email' => 'required|email',
            'age' => 'required|integer|min:18'
        ];
        
        $invalidData = [
            'name' => '',
            'email' => 'invalid-email',
            'age' => 16
        ];
        
        // 批量验证应该收集所有错误
        $this->validate->batch(true);
        $this->assertFalse($this->validate->check($invalidData, $rules));
        
        $errors = $this->validate->getError();
        $this->assertIsArray($errors);
        $this->assertArrayHasKey('name', $errors);
        $this->assertArrayHasKey('email', $errors);
        $this->assertArrayHasKey('age', $errors);
    }
    
    /**
     * 测试自定义错误消息
     */
    public function testCustomErrorMessages()
    {
        $rules = ['name' => 'required|min:3'];
        $messages = [
            'name.required' => '姓名不能为空',
            'name.min' => '姓名至少需要3个字符'
        ];
        
        $validate = new Validate($rules, $messages);
        
        // 测试required错误消息
        $validate->check(['name' => '']);
        $this->assertEquals('姓名不能为空', $validate->getError());
        
        // 测试min错误消息
        $validate->check(['name' => 'ab']);
        $this->assertEquals('姓名至少需要3个字符', $validate->getError());
    }
    
    /**
     * 测试字段描述
     */
    public function testFieldDescriptions()
    {
        $rules = ['user_name|用户名' => 'required'];
        
        $this->validate->check(['user_name' => ''], $rules);
        $error = $this->validate->getError();
        
        // 错误消息应该包含字段描述而不是字段名
        $this->assertStringContainsString('用户名', $error);
    }
} 