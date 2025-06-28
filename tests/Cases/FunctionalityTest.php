<?php

declare(strict_types=1);

namespace HPlus\Validate\Tests\Cases;

use HPlus\Validate\Validate;

/**
 * 功能完整性测试
 * 确保所有验证规则都正常工作
 */
class FunctionalityTest extends AbstractTestCase
{
    protected Validate $validate;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->validate = new Validate();
    }
    
    /**
     * 测试所有字符串验证规则
     */
    public function testStringValidationRules()
    {
        // chs - 纯中文
        $this->assertTrue($this->validate->check(['name' => '张三'], ['name' => 'chs']));
        $this->assertFalse($this->validate->check(['name' => 'zhangsan'], ['name' => 'chs']));
        $this->assertFalse($this->validate->check(['name' => '张san'], ['name' => 'chs']));
        
        // chsAlpha - 中文字母
        $this->assertTrue($this->validate->check(['name' => '张三abc'], ['name' => 'chsAlpha']));
        $this->assertTrue($this->validate->check(['name' => 'ABC张三'], ['name' => 'chsAlpha']));
        $this->assertFalse($this->validate->check(['name' => '张三123'], ['name' => 'chsAlpha']));
        
        // chsAlphaNum - 中文字母数字
        $this->assertTrue($this->validate->check(['name' => '张三abc123'], ['name' => 'chsAlphaNum']));
        $this->assertFalse($this->validate->check(['name' => '张三@abc'], ['name' => 'chsAlphaNum']));
        
        // chsDash - 中文字母数字下划线破折号
        $this->assertTrue($this->validate->check(['name' => '张三_abc-123'], ['name' => 'chsDash']));
        $this->assertFalse($this->validate->check(['name' => '张三 abc'], ['name' => 'chsDash']));
        
        // lower - 小写
        $this->assertTrue($this->validate->check(['code' => 'abc'], ['code' => 'lower']));
        $this->assertFalse($this->validate->check(['code' => 'ABC'], ['code' => 'lower']));
        
        // upper - 大写
        $this->assertTrue($this->validate->check(['code' => 'ABC'], ['code' => 'upper']));
        $this->assertFalse($this->validate->check(['code' => 'abc'], ['code' => 'upper']));
    }
    
    /**
     * 测试所有数字验证规则
     */
    public function testNumberValidationRules()
    {
        // float - 浮点数
        $this->assertTrue($this->validate->check(['price' => 19.99], ['price' => 'float']));
        $this->assertTrue($this->validate->check(['price' => '19.99'], ['price' => 'float']));
        $this->assertFalse($this->validate->check(['price' => 'abc'], ['price' => 'float']));
        
        // number - 数字（整数或浮点）
        $this->assertTrue($this->validate->check(['num' => 123], ['num' => 'number']));
        $this->assertTrue($this->validate->check(['num' => 123.45], ['num' => 'number']));
        $this->assertTrue($this->validate->check(['num' => '123.45'], ['num' => 'number']));
        $this->assertFalse($this->validate->check(['num' => 'abc'], ['num' => 'number']));
        
        // 数学比较
        $this->assertTrue($this->validate->check(['age' => 20], ['age' => 'gt:18']));
        $this->assertTrue($this->validate->check(['age' => 18], ['age' => 'egt:18']));
        $this->assertTrue($this->validate->check(['age' => 16], ['age' => 'lt:18']));
        $this->assertTrue($this->validate->check(['age' => 18], ['age' => 'elt:18']));
        $this->assertTrue($this->validate->check(['age' => 18], ['age' => 'eq:18']));
        $this->assertTrue($this->validate->check(['age' => 20], ['age' => 'neq:18']));
    }
    
    /**
     * 测试日期时间验证规则
     */
    public function testDateTimeValidationRules()
    {
        // dateFormat - 日期格式
        $this->assertTrue($this->validate->check(['date' => '2023-12-25'], ['date' => 'dateFormat:Y-m-d']));
        $this->assertTrue($this->validate->check(['date' => '25/12/2023'], ['date' => 'dateFormat:d/m/Y']));
        $this->assertFalse($this->validate->check(['date' => '2023-13-01'], ['date' => 'dateFormat:Y-m-d']));
        
        // time - 时间格式
        $this->assertTrue($this->validate->check(['time' => '14:30:00'], ['time' => 'dateFormat:H:i:s']));
        $this->assertTrue($this->validate->check(['time' => '2:30 PM'], ['time' => 'dateFormat:g:i A']));
        
        // before/after - 日期比较
        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $tomorrow = date('Y-m-d', strtotime('+1 day'));
        
        $this->assertTrue($this->validate->check(['date' => $yesterday], ['date' => "before:{$today}"]));
        $this->assertFalse($this->validate->check(['date' => $tomorrow], ['date' => "before:{$today}"]));
        
        $this->assertTrue($this->validate->check(['date' => $tomorrow], ['date' => "after:{$today}"]));
        $this->assertFalse($this->validate->check(['date' => $yesterday], ['date' => "after:{$today}"]));
        
        // beforeTime/afterTime - 时间戳比较
        $now = time();
        $past = $now - 3600;
        $future = $now + 3600;
        
        $this->assertTrue($this->validate->check(['time' => $past], ['time' => "beforeTime:{$now}"]));
        $this->assertTrue($this->validate->check(['time' => $future], ['time' => "afterTime:{$now}"]));
    }
    
    /**
     * 测试文件验证规则
     */
    public function testFileValidationRules()
    {
        // 模拟文件数据
        $imageFile = [
            'name' => 'test.jpg',
            'type' => 'image/jpeg',
            'size' => 1024 * 500, // 500KB
            'tmp_name' => '/tmp/test.jpg',
            'error' => 0
        ];
        
        $docFile = [
            'name' => 'document.pdf',
            'type' => 'application/pdf',
            'size' => 1024 * 1024 * 2, // 2MB
            'tmp_name' => '/tmp/document.pdf',
            'error' => 0
        ];
        
        // file - 是否是文件
        $this->assertTrue($this->validate->check(['upload' => $imageFile], ['upload' => 'file']));
        $this->assertFalse($this->validate->check(['upload' => 'not a file'], ['upload' => 'file']));
        
        // image - 图片文件
        $this->assertTrue($this->validate->check(['photo' => $imageFile], ['photo' => 'image']));
        $this->assertFalse($this->validate->check(['photo' => $docFile], ['photo' => 'image']));
        
        // fileExt - 文件扩展名
        $this->assertTrue($this->validate->check(['doc' => $docFile], ['doc' => 'fileExt:pdf,doc,docx']));
        $this->assertFalse($this->validate->check(['doc' => $imageFile], ['doc' => 'fileExt:pdf,doc,docx']));
        
        // fileMime - MIME类型
        $this->assertTrue($this->validate->check(['img' => $imageFile], ['img' => 'fileMime:image/jpeg,image/png']));
        $this->assertFalse($this->validate->check(['img' => $docFile], ['img' => 'fileMime:image/jpeg,image/png']));
        
        // fileSize - 文件大小
        $this->assertTrue($this->validate->check(['file' => $imageFile], ['file' => 'fileSize:1048576'])); // 1MB
        $this->assertFalse($this->validate->check(['file' => $docFile], ['file' => 'fileSize:1048576'])); // 2MB > 1MB
    }
    
    /**
     * 测试特殊验证规则
     */
    public function testSpecialValidationRules()
    {
        // token - CSRF令牌验证
        $_POST['__token__'] = 'test_token_123';
        $this->assertTrue($this->validate->check(['__token__' => 'test_token_123'], ['__token__' => 'token']));
        
        // unique - 唯一性验证（需要数据库支持，这里仅示例）
        // $this->assertTrue($this->validate->check(['username' => 'newuser'], ['username' => 'unique:users']));
        
        // regex - 正则表达式
        $this->assertTrue($this->validate->check(['code' => 'ABC123'], ['code' => 'regex:/^[A-Z]+[0-9]+$/']));
        $this->assertFalse($this->validate->check(['code' => 'abc123'], ['code' => 'regex:/^[A-Z]+[0-9]+$/']));
        
        // notRegex - 不匹配正则
        $this->assertTrue($this->validate->check(['text' => 'hello world'], ['text' => 'notRegex:/[0-9]/']));
        $this->assertFalse($this->validate->check(['text' => 'hello123'], ['text' => 'notRegex:/[0-9]/']));
        
        // activeUrl - 活跃的URL（需要DNS查询）
        // $this->assertTrue($this->validate->check(['url' => 'https://www.google.com'], ['url' => 'activeUrl']));
        
        // json - JSON字符串
        $this->assertTrue($this->validate->check(['data' => '{"name":"test"}'], ['data' => 'json']));
        $this->assertFalse($this->validate->check(['data' => '{invalid json}'], ['data' => 'json']));
        
        // xml - XML字符串
        $this->assertTrue($this->validate->check(['data' => '<root><name>test</name></root>'], ['data' => 'xml']));
        $this->assertFalse($this->validate->check(['data' => '<invalid xml'], ['data' => 'xml']));
    }
    
    /**
     * 测试数组验证规则
     */
    public function testArrayValidationRules()
    {
        // array - 是否是数组
        $this->assertTrue($this->validate->check(['items' => [1, 2, 3]], ['items' => 'array']));
        $this->assertFalse($this->validate->check(['items' => 'not array'], ['items' => 'array']));
        
        // 数组长度验证
        $this->assertTrue($this->validate->check(['items' => [1, 2, 3]], ['items' => 'array|min:2']));
        $this->assertFalse($this->validate->check(['items' => [1]], ['items' => 'array|min:2']));
        
        $this->assertTrue($this->validate->check(['items' => [1, 2]], ['items' => 'array|max:3']));
        $this->assertFalse($this->validate->check(['items' => [1, 2, 3, 4]], ['items' => 'array|max:3']));
        
        $this->assertTrue($this->validate->check(['items' => [1, 2, 3]], ['items' => 'array|length:3']));
        $this->assertFalse($this->validate->check(['items' => [1, 2]], ['items' => 'array|length:3']));
        
        // 多维数组验证
        $data = [
            'users' => [
                ['name' => 'User1', 'age' => 25],
                ['name' => 'User2', 'age' => 30]
            ]
        ];
        
        // 注意：原始Validate可能不支持通配符，这里仅作示例
        // $this->assertTrue($this->validate->check($data, [
        //     'users' => 'array',
        //     'users.*.name' => 'required|string',
        //     'users.*.age' => 'required|integer|min:18'
        // ]));
    }
    
    /**
     * 测试条件验证规则
     */
    public function testConditionalValidationRules()
    {
        // requireIf - 条件必填
        $data1 = ['type' => 'company', 'company_name' => 'Test Inc'];
        $data2 = ['type' => 'personal'];
        $data3 = ['type' => 'company']; // 缺少company_name
        
        $this->assertTrue($this->validate->check($data1, ['company_name' => 'requireIf:type,company']));
        $this->assertTrue($this->validate->check($data2, ['company_name' => 'requireIf:type,company']));
        $this->assertFalse($this->validate->check($data3, ['company_name' => 'requireIf:type,company']));
        
        // requireWith - 依赖必填
        $this->assertTrue($this->validate->check(
            ['password' => '123', 'password_confirm' => '123'],
            ['password_confirm' => 'requireWith:password']
        ));
        $this->assertFalse($this->validate->check(
            ['password' => '123'],
            ['password_confirm' => 'requireWith:password']
        ));
        
        // requireWithout - 缺失时必填
        $this->assertTrue($this->validate->check(
            ['email' => 'test@example.com'],
            ['phone' => 'requireWithout:email']
        ));
        $this->assertFalse($this->validate->check(
            [],
            ['phone' => 'requireWithout:email']
        ));
        
        // requireWithAll - 所有字段存在时必填
        $this->assertTrue($this->validate->check(
            ['first_name' => 'John', 'last_name' => 'Doe', 'full_name' => 'John Doe'],
            ['full_name' => 'requireWithAll:first_name,last_name']
        ));
        
        // requireWithoutAll - 所有字段都不存在时必填
        $this->assertTrue($this->validate->check(
            ['username' => 'johndoe'],
            ['username' => 'requireWithoutAll:email,phone']
        ));
    }
    
    /**
     * 测试字段比较验证
     */
    public function testFieldComparisonRules()
    {
        // same - 相同
        $this->assertTrue($this->validate->check(
            ['password' => '123456', 'confirm' => '123456'],
            ['confirm' => 'same:password']
        ));
        $this->assertFalse($this->validate->check(
            ['password' => '123456', 'confirm' => '654321'],
            ['confirm' => 'same:password']
        ));
        
        // different - 不同
        $this->assertTrue($this->validate->check(
            ['username' => 'user1', 'nickname' => 'nick1'],
            ['nickname' => 'different:username']
        ));
        $this->assertFalse($this->validate->check(
            ['username' => 'same', 'nickname' => 'same'],
            ['nickname' => 'different:username']
        ));
        
        // gtField/ltField - 字段比较
        $this->assertTrue($this->validate->check(
            ['min_price' => 100, 'max_price' => 200],
            ['max_price' => 'gtField:min_price']
        ));
        $this->assertTrue($this->validate->check(
            ['start_date' => '2023-01-01', 'end_date' => '2023-12-31'],
            ['start_date' => 'ltField:end_date']
        ));
    }
    
    /**
     * 测试IP和网络验证
     */
    public function testNetworkValidationRules()
    {
        // ip - IPv4
        $this->assertTrue($this->validate->check(['ip' => '192.168.1.1'], ['ip' => 'ip']));
        $this->assertTrue($this->validate->check(['ip' => '8.8.8.8'], ['ip' => 'ip']));
        $this->assertFalse($this->validate->check(['ip' => '999.999.999.999'], ['ip' => 'ip']));
        $this->assertFalse($this->validate->check(['ip' => 'not.an.ip'], ['ip' => 'ip']));
        
        // ipv4
        $this->assertTrue($this->validate->check(['ip' => '127.0.0.1'], ['ip' => 'ipv4']));
        $this->assertFalse($this->validate->check(['ip' => '::1'], ['ip' => 'ipv4']));
        
        // ipv6
        $this->assertTrue($this->validate->check(['ip' => '::1'], ['ip' => 'ipv6']));
        $this->assertTrue($this->validate->check(['ip' => '2001:0db8:85a3:0000:0000:8a2e:0370:7334'], ['ip' => 'ipv6']));
        $this->assertFalse($this->validate->check(['ip' => '192.168.1.1'], ['ip' => 'ipv6']));
        
        // mac - MAC地址
        $this->assertTrue($this->validate->check(['mac' => '00:11:22:33:44:55'], ['mac' => 'regex:/^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/']));
        $this->assertTrue($this->validate->check(['mac' => 'AA-BB-CC-DD-EE-FF'], ['mac' => 'regex:/^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/']));
    }
    
    /**
     * 测试中国特色验证规则
     */
    public function testChineseValidationRules()
    {
        // mobile - 中国手机号
        $this->assertTrue($this->validate->check(['phone' => '13800138000'], ['phone' => 'mobile']));
        $this->assertTrue($this->validate->check(['phone' => '15912345678'], ['phone' => 'mobile']));
        $this->assertFalse($this->validate->check(['phone' => '12345678901'], ['phone' => 'mobile']));
        $this->assertFalse($this->validate->check(['phone' => '138001380001'], ['phone' => 'mobile']));
        
        // idCard - 身份证号（简单验证）
        $this->assertTrue($this->validate->check(['id' => '110101199001011234'], ['id' => 'regex:/^[1-9]\d{5}(19|20)\d{2}(0[1-9]|1[0-2])(0[1-9]|[12]\d|3[01])\d{3}[\dX]$/']));
        $this->assertFalse($this->validate->check(['id' => '123456789012345678'], ['id' => 'regex:/^[1-9]\d{5}(19|20)\d{2}(0[1-9]|1[0-2])(0[1-9]|[12]\d|3[01])\d{3}[\dX]$/']));
        
        // zip - 邮政编码
        $this->assertTrue($this->validate->check(['zip' => '100000'], ['zip' => 'regex:/^[0-9]{6}$/']));
        $this->assertFalse($this->validate->check(['zip' => '10000'], ['zip' => 'regex:/^[0-9]{6}$/']));
        $this->assertFalse($this->validate->check(['zip' => '1000000'], ['zip' => 'regex:/^[0-9]{6}$/']));
    }
} 