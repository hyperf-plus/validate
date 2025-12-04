<?php

declare(strict_types=1);

namespace HPlus\Validate\Tests\Feature;

use HPlus\Validate\Annotations\RequestValidation;
use HPlus\Validate\Aspect\ValidationAspect;
use HPlus\Validate\Exception\ValidateException;
use HPlus\Validate\Tests\TestCase;

/**
 * 验证规则功能测试
 */
class ValidationRulesTest extends TestCase
{
    /**
     * 测试必填规则
     */
    public function testRequiredRule(): void
    {
        echo "\n[测试] Required 规则 - 应该失败\n";
        echo "数据: []\n";
        echo "规则: name => required\n";
        
        $this->expectException(ValidateException::class);

        $container = $this->createContainerWithRequest([], []);
        $aspect = new ValidationAspect(
            $container,
            $container->get(\Hyperf\Validation\Contract\ValidatorFactoryInterface::class)
        );

        $joinPoint = $this->createMockJoinPoint([
            new RequestValidation(rules: ['name' => 'required'])
        ]);

        try {
            $aspect->process($joinPoint);
            echo "❌ 验证通过了（不应该）\n";
        } catch (ValidateException $e) {
            echo "✅ 验证失败: " . $e->getMessage() . "\n";
            throw $e;
        }
    }

    /**
     * 测试字符串规则
     */
    public function testStringRule(): void
    {
        echo "\n[测试] String 规则 - 应该失败\n";
        echo "数据: name => 123 (整数)\n";
        echo "规则: name => string\n";
        
        $this->expectException(ValidateException::class);

        $container = $this->createContainerWithRequest([], ['name' => 123]);
        $aspect = new ValidationAspect(
            $container,
            $container->get(\Hyperf\Validation\Contract\ValidatorFactoryInterface::class)
        );

        $joinPoint = $this->createMockJoinPoint([
            new RequestValidation(rules: ['name' => 'string'])
        ]);

        try {
            $aspect->process($joinPoint);
            echo "❌ 验证通过了（不应该）\n";
        } catch (ValidateException $e) {
            echo "✅ 验证失败: " . $e->getMessage() . "\n";
            throw $e;
        }
    }

    /**
     * 测试整数规则 - 通过
     */
    public function testIntegerRulePass(): void
    {
        echo "\n[测试] Integer 规则 - 应该通过\n";
        echo "数据: age => 25\n";
        echo "规则: age => integer\n";
        
        $container = $this->createContainerWithRequest([], ['age' => 25]);
        $aspect = new ValidationAspect(
            $container,
            $container->get(\Hyperf\Validation\Contract\ValidatorFactoryInterface::class)
        );

        $joinPoint = $this->createMockJoinPoint([
            new RequestValidation(rules: ['age' => 'integer'])
        ]);

        $result = $aspect->process($joinPoint);
        echo "✅ 验证通过，返回: " . $result . "\n";
        $this->assertEquals('processed', $result);
    }

    /**
     * 测试整数规则 - 失败
     */
    public function testIntegerRuleFails(): void
    {
        echo "\n[测试] Integer 规则 - 应该失败\n";
        echo "数据: age => 'not-integer' (字符串)\n";
        echo "规则: age => required|integer\n";
        
        $this->expectException(ValidateException::class);

        $container = $this->createContainerWithRequest([], ['age' => 'not-integer']);
        $aspect = new ValidationAspect(
            $container,
            $container->get(\Hyperf\Validation\Contract\ValidatorFactoryInterface::class)
        );

        $joinPoint = $this->createMockJoinPoint([
            new RequestValidation(rules: ['age' => 'required|integer'])
        ]);

        try {
            $aspect->process($joinPoint);
            echo "❌ 验证通过了（不应该）\n";
        } catch (ValidateException $e) {
            echo "✅ 验证失败: " . $e->getMessage() . "\n";
            throw $e;
        }
    }

    /**
     * 测试邮箱规则
     */
    public function testEmailRule(): void
    {
        echo "\n[测试] Email 规则 - 应该失败\n";
        echo "数据: email => 'invalid'\n";
        echo "规则: email => email\n";
        
        $this->expectException(ValidateException::class);

        $container = $this->createContainerWithRequest([], ['email' => 'invalid']);
        $aspect = new ValidationAspect(
            $container,
            $container->get(\Hyperf\Validation\Contract\ValidatorFactoryInterface::class)
        );

        $joinPoint = $this->createMockJoinPoint([
            new RequestValidation(rules: ['email' => 'email'])
        ]);

        try {
            $aspect->process($joinPoint);
            echo "❌ 验证通过了（不应该）\n";
        } catch (ValidateException $e) {
            echo "✅ 验证失败: " . $e->getMessage() . "\n";
            throw $e;
        }
    }

    /**
     * 测试最小值规则
     */
    public function testMinRule(): void
    {
        echo "\n[测试] Min 规则 - 应该失败\n";
        echo "数据: age => 5\n";
        echo "规则: age => integer|min:18\n";
        echo "期望: 失败，因为 5 < 18\n";
        
        $this->expectException(ValidateException::class);

        $container = $this->createContainerWithRequest([], ['age' => 5]);
        $aspect = new ValidationAspect(
            $container,
            $container->get(\Hyperf\Validation\Contract\ValidatorFactoryInterface::class)
        );

        $joinPoint = $this->createMockJoinPoint([
            new RequestValidation(rules: ['age' => 'integer|min:18'])
        ]);

        try {
            $aspect->process($joinPoint);
            echo "❌ 验证通过了（不应该）\n";
        } catch (ValidateException $e) {
            echo "✅ 验证失败: " . $e->getMessage() . "\n";
            throw $e;
        }
    }

    /**
     * 测试最大值规则
     */
    public function testMaxRule(): void
    {
        echo "\n[测试] Max 规则 - 应该失败\n";
        echo "数据: name => 101个字符\n";
        echo "规则: name => string|max:100\n";
        echo "期望: 失败，因为长度 > 100\n";
        
        $this->expectException(ValidateException::class);

        $container = $this->createContainerWithRequest([], ['name' => str_repeat('a', 101)]);
        $aspect = new ValidationAspect(
            $container,
            $container->get(\Hyperf\Validation\Contract\ValidatorFactoryInterface::class)
        );

        $joinPoint = $this->createMockJoinPoint([
            new RequestValidation(rules: ['name' => 'string|max:100'])
        ]);

        try {
            $aspect->process($joinPoint);
            echo "❌ 验证通过了（不应该）\n";
        } catch (ValidateException $e) {
            echo "✅ 验证失败: " . $e->getMessage() . "\n";
            throw $e;
        }
    }

    /**
     * 测试范围规则 - 通过
     */
    public function testBetweenRulePass(): void
    {
        echo "\n[测试] Between 规则 - 应该通过\n";
        echo "数据: age => 25\n";
        echo "规则: age => integer|between:18,100\n";
        echo "期望: 通过，因为 18 <= 25 <= 100\n";
        
        $container = $this->createContainerWithRequest([], ['age' => 25]);
        $aspect = new ValidationAspect(
            $container,
            $container->get(\Hyperf\Validation\Contract\ValidatorFactoryInterface::class)
        );

        $joinPoint = $this->createMockJoinPoint([
            new RequestValidation(rules: ['age' => 'integer|between:18,100'])
        ]);

        $result = $aspect->process($joinPoint);
        echo "✅ 验证通过，返回: " . $result . "\n";
        $this->assertEquals('processed', $result);
    }

    /**
     * 测试范围规则 - 边界值测试
     */
    public function testBetweenRuleBoundary(): void
    {
        // 测试最小边界（18 应该通过）
        $container1 = $this->createContainerWithRequest([], ['age' => 18]);
        $aspect1 = new ValidationAspect(
            $container1,
            $container1->get(\Hyperf\Validation\Contract\ValidatorFactoryInterface::class)
        );
        $joinPoint1 = $this->createMockJoinPoint([
            new RequestValidation(rules: ['age' => 'integer|between:18,100'])
        ]);
        $this->assertEquals('processed', $aspect1->process($joinPoint1));

        // 测试最大边界（100 应该通过）
        $container2 = $this->createContainerWithRequest([], ['age' => 100]);
        $aspect2 = new ValidationAspect(
            $container2,
            $container2->get(\Hyperf\Validation\Contract\ValidatorFactoryInterface::class)
        );
        $joinPoint2 = $this->createMockJoinPoint([
            new RequestValidation(rules: ['age' => 'integer|between:18,100'])
        ]);
        $this->assertEquals('processed', $aspect2->process($joinPoint2));
    }

    /**
     * 测试范围规则 - 失败
     */
    public function testBetweenRuleFails(): void
    {
        echo "\n[测试] Between 规则 - 应该失败（低于最小值）\n";
        echo "数据: age => 17\n";
        echo "规则: age => required|integer|between:18,100\n";
        echo "期望: 失败，因为 17 < 18\n";
        
        $this->expectException(ValidateException::class);

        // 小于最小值应该失败
        $container = $this->createContainerWithRequest([], ['age' => 17]);
        $aspect = new ValidationAspect(
            $container,
            $container->get(\Hyperf\Validation\Contract\ValidatorFactoryInterface::class)
        );

        $joinPoint = $this->createMockJoinPoint([
            new RequestValidation(rules: ['age' => 'required|integer|between:18,100'])
        ]);

        try {
            $aspect->process($joinPoint);
            echo "❌ 验证通过了（不应该）\n";
        } catch (ValidateException $e) {
            echo "✅ 验证失败: " . $e->getMessage() . "\n";
            throw $e;
        }
    }

    /**
     * 测试枚举规则
     */
    public function testInRule(): void
    {
        echo "\n[测试] In 规则 - 应该失败\n";
        echo "数据: status => 'invalid'\n";
        echo "规则: status => in:pending,approved,rejected\n";
        echo "期望: 失败，因为 'invalid' 不在允许的值中\n";
        
        $this->expectException(ValidateException::class);

        $container = $this->createContainerWithRequest([], ['status' => 'invalid']);
        $aspect = new ValidationAspect(
            $container,
            $container->get(\Hyperf\Validation\Contract\ValidatorFactoryInterface::class)
        );

        $joinPoint = $this->createMockJoinPoint([
            new RequestValidation(rules: ['status' => 'in:pending,approved,rejected'])
        ]);

        try {
            $aspect->process($joinPoint);
            echo "❌ 验证通过了（不应该）\n";
        } catch (ValidateException $e) {
            echo "✅ 验证失败: " . $e->getMessage() . "\n";
            throw $e;
        }
    }

    /**
     * 测试数组规则 - 通过
     */
    public function testArrayRulePass(): void
    {
        echo "\n[测试] Array 规则 - 应该通过\n";
        echo "数据: tags => ['tag1', 'tag2']\n";
        echo "规则: tags => array\n";
        
        $container = $this->createContainerWithRequest([], ['tags' => ['tag1', 'tag2']]);
        $aspect = new ValidationAspect(
            $container,
            $container->get(\Hyperf\Validation\Contract\ValidatorFactoryInterface::class)
        );

        $joinPoint = $this->createMockJoinPoint([
            new RequestValidation(rules: ['tags' => 'array'])
        ]);

        $result = $aspect->process($joinPoint);
        echo "✅ 验证通过，返回: " . $result . "\n";
        $this->assertEquals('processed', $result);
    }

    /**
     * 测试数组规则 - 失败
     */
    public function testArrayRuleFails(): void
    {
        echo "\n[测试] Array 规则 - 应该失败\n";
        echo "数据: tags => 'not-array' (字符串)\n";
        echo "规则: tags => required|array\n";
        
        $this->expectException(ValidateException::class);

        $container = $this->createContainerWithRequest([], ['tags' => 'not-array']);
        $aspect = new ValidationAspect(
            $container,
            $container->get(\Hyperf\Validation\Contract\ValidatorFactoryInterface::class)
        );

        $joinPoint = $this->createMockJoinPoint([
            new RequestValidation(rules: ['tags' => 'required|array'])
        ]);

        try {
            $aspect->process($joinPoint);
            echo "❌ 验证通过了（不应该）\n";
        } catch (ValidateException $e) {
            echo "✅ 验证失败: " . $e->getMessage() . "\n";
            throw $e;
        }
    }

    /**
     * 测试嵌套数组规则
     */
    public function testNestedArrayRule(): void
    {
        echo "\n[测试] 嵌套数组规则 - 应该通过\n";
        echo "数据: users => [2个用户，每个有name和email]\n";
        echo "规则: users.*.name => required|string, users.*.email => required|email\n";
        
        $container = $this->createContainerWithRequest([], [
            'users' => [
                ['name' => 'John', 'email' => 'john@example.com'],
                ['name' => 'Jane', 'email' => 'jane@example.com'],
            ]
        ]);
        
        $aspect = new ValidationAspect(
            $container,
            $container->get(\Hyperf\Validation\Contract\ValidatorFactoryInterface::class)
        );

        $joinPoint = $this->createMockJoinPoint([
            new RequestValidation(rules: [
                'users' => 'required|array',
                'users.*.name' => 'required|string',
                'users.*.email' => 'required|email',
            ])
        ]);

        $result = $aspect->process($joinPoint);
        echo "✅ 验证通过，返回: " . $result . "\n";
        $this->assertEquals('processed', $result);
    }

    /**
     * 测试确认字段规则
     */
    public function testConfirmedRule(): void
    {
        echo "\n[测试] Confirmed 规则 - 应该通过\n";
        echo "数据: password => 'secret123', password_confirmation => 'secret123'\n";
        echo "规则: password => required|confirmed\n";
        echo "期望: 通过，因为两次密码一致\n";
        
        $container = $this->createContainerWithRequest([], [
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]);
        
        $aspect = new ValidationAspect(
            $container,
            $container->get(\Hyperf\Validation\Contract\ValidatorFactoryInterface::class)
        );

        $joinPoint = $this->createMockJoinPoint([
            new RequestValidation(rules: ['password' => 'required|confirmed'])
        ]);

        $result = $aspect->process($joinPoint);
        echo "✅ 验证通过，返回: " . $result . "\n";
        $this->assertEquals('processed', $result);
    }

    /**
     * 测试确认字段规则失败
     */
    public function testConfirmedRuleFails(): void
    {
        echo "\n[测试] Confirmed 规则 - 应该失败\n";
        echo "数据: password => 'secret123', password_confirmation => 'different'\n";
        echo "规则: password => required|confirmed\n";
        echo "期望: 失败，因为两次密码不一致\n";
        
        $this->expectException(ValidateException::class);

        $container = $this->createContainerWithRequest([], [
            'password' => 'secret123',
            'password_confirmation' => 'different',
        ]);
        
        $aspect = new ValidationAspect(
            $container,
            $container->get(\Hyperf\Validation\Contract\ValidatorFactoryInterface::class)
        );

        $joinPoint = $this->createMockJoinPoint([
            new RequestValidation(rules: ['password' => 'required|confirmed'])
        ]);

        try {
            $aspect->process($joinPoint);
            echo "❌ 验证通过了（不应该）\n";
        } catch (ValidateException $e) {
            echo "✅ 验证失败: " . $e->getMessage() . "\n";
            throw $e;
        }
    }

    /**
     * 测试 URL 规则 - 通过
     */
    public function testUrlRulePass(): void
    {
        echo "\n[测试] URL 规则 - 应该通过\n";
        echo "数据: website => 'https://example.com'\n";
        echo "规则: website => url\n";
        
        $container = $this->createContainerWithRequest([], ['website' => 'https://example.com']);
        $aspect = new ValidationAspect(
            $container,
            $container->get(\Hyperf\Validation\Contract\ValidatorFactoryInterface::class)
        );

        $joinPoint = $this->createMockJoinPoint([
            new RequestValidation(rules: ['website' => 'url'])
        ]);

        $result = $aspect->process($joinPoint);
        echo "✅ 验证通过，返回: " . $result . "\n";
        $this->assertEquals('processed', $result);
    }

    /**
     * 测试 URL 规则 - 失败
     */
    public function testUrlRuleFails(): void
    {
        echo "\n[测试] URL 规则 - 应该失败\n";
        echo "数据: website => 'not-a-url'\n";
        echo "规则: website => required|url\n";
        
        $this->expectException(ValidateException::class);

        $container = $this->createContainerWithRequest([], ['website' => 'not-a-url']);
        $aspect = new ValidationAspect(
            $container,
            $container->get(\Hyperf\Validation\Contract\ValidatorFactoryInterface::class)
        );

        $joinPoint = $this->createMockJoinPoint([
            new RequestValidation(rules: ['website' => 'required|url'])
        ]);

        try {
            $aspect->process($joinPoint);
            echo "❌ 验证通过了（不应该）\n";
        } catch (ValidateException $e) {
            echo "✅ 验证失败: " . $e->getMessage() . "\n";
            throw $e;
        }
    }

    /**
     * 测试布尔规则 - 通过
     */
    public function testBooleanRulePass(): void
    {
        echo "\n[测试] Boolean 规则 - 应该通过\n";
        echo "数据: active => true\n";
        echo "规则: active => boolean\n";
        
        $container = $this->createContainerWithRequest([], ['active' => true]);
        $aspect = new ValidationAspect(
            $container,
            $container->get(\Hyperf\Validation\Contract\ValidatorFactoryInterface::class)
        );

        $joinPoint = $this->createMockJoinPoint([
            new RequestValidation(rules: ['active' => 'boolean'])
        ]);

        $result = $aspect->process($joinPoint);
        echo "✅ 验证通过，返回: " . $result . "\n";
        $this->assertEquals('processed', $result);
    }

    /**
     * 测试布尔规则 - 失败
     */
    public function testBooleanRuleFails(): void
    {
        echo "\n[测试] Boolean 规则 - 应该失败\n";
        echo "数据: active => 'yes' (字符串)\n";
        echo "规则: active => required|boolean\n";
        
        $this->expectException(ValidateException::class);

        $container = $this->createContainerWithRequest([], ['active' => 'yes']);
        $aspect = new ValidationAspect(
            $container,
            $container->get(\Hyperf\Validation\Contract\ValidatorFactoryInterface::class)
        );

        $joinPoint = $this->createMockJoinPoint([
            new RequestValidation(rules: ['active' => 'required|boolean'])
        ]);

        try {
            $aspect->process($joinPoint);
            echo "❌ 验证通过了（不应该）\n";
        } catch (ValidateException $e) {
            echo "✅ 验证失败: " . $e->getMessage() . "\n";
            throw $e;
        }
    }

    /**
     * 测试正则表达式规则 - 通过
     */
    public function testRegexRulePass(): void
    {
        echo "\n[测试] Regex 规则 - 应该通过\n";
        echo "数据: code => 'ABC123'\n";
        echo "规则: code => regex:/^[A-Z]{3}[0-9]{3}$/\n";
        echo "期望: 通过，因为匹配 3个大写字母+3个数字\n";
        
        $container = $this->createContainerWithRequest([], ['code' => 'ABC123']);
        $aspect = new ValidationAspect(
            $container,
            $container->get(\Hyperf\Validation\Contract\ValidatorFactoryInterface::class)
        );

        $joinPoint = $this->createMockJoinPoint([
            new RequestValidation(rules: ['code' => 'regex:/^[A-Z]{3}[0-9]{3}$/'])
        ]);

        $result = $aspect->process($joinPoint);
        echo "✅ 验证通过，返回: " . $result . "\n";
        $this->assertEquals('processed', $result);
    }

    /**
     * 测试正则表达式规则 - 失败
     */
    public function testRegexRuleFails(): void
    {
        echo "\n[测试] Regex 规则 - 应该失败\n";
        echo "数据: code => 'abc123' (小写)\n";
        echo "规则: code => required|regex:/^[A-Z]{3}[0-9]{3}$/\n";
        echo "期望: 失败，因为不匹配正则\n";
        
        $this->expectException(ValidateException::class);

        $container = $this->createContainerWithRequest([], ['code' => 'abc123']);
        $aspect = new ValidationAspect(
            $container,
            $container->get(\Hyperf\Validation\Contract\ValidatorFactoryInterface::class)
        );

        $joinPoint = $this->createMockJoinPoint([
            new RequestValidation(rules: ['code' => 'required|regex:/^[A-Z]{3}[0-9]{3}$/'])
        ]);

        try {
            $aspect->process($joinPoint);
            echo "❌ 验证通过了（不应该）\n";
        } catch (ValidateException $e) {
            echo "✅ 验证失败: " . $e->getMessage() . "\n";
            throw $e;
        }
    }

    /**
     * 测试可空规则
     */
    public function testNullableRule(): void
    {
        echo "\n[测试] Nullable 规则 - 应该通过\n";
        echo "数据: description => null\n";
        echo "规则: description => nullable|string\n";
        echo "期望: 通过，因为允许为空\n";
        
        $container = $this->createContainerWithRequest([], ['description' => null]);
        $aspect = new ValidationAspect(
            $container,
            $container->get(\Hyperf\Validation\Contract\ValidatorFactoryInterface::class)
        );

        $joinPoint = $this->createMockJoinPoint([
            new RequestValidation(rules: ['description' => 'nullable|string'])
        ]);

        $result = $aspect->process($joinPoint);
        echo "✅ 验证通过（允许null），返回: " . $result . "\n";
        $this->assertEquals('processed', $result);
    }

    /**
     * 测试 sometimes 规则
     */
    public function testSometimesRule(): void
    {
        echo "\n[测试] Sometimes 规则 - 应该通过\n";
        echo "数据: [] (字段不存在)\n";
        echo "规则: optional_field => sometimes|string|min:5\n";
        echo "期望: 通过，因为字段不存在时不验证\n";
        
        // 字段不存在时不验证
        $container = $this->createContainerWithRequest([], []);
        $aspect = new ValidationAspect(
            $container,
            $container->get(\Hyperf\Validation\Contract\ValidatorFactoryInterface::class)
        );

        $joinPoint = $this->createMockJoinPoint([
            new RequestValidation(rules: ['optional_field' => 'sometimes|string|min:5'])
        ]);

        $result = $aspect->process($joinPoint);
        echo "✅ 验证通过（字段不存在时跳过验证），返回: " . $result . "\n";
        $this->assertEquals('processed', $result);
    }

    /**
     * 测试条件必填规则 - required_if 通过
     */
    public function testRequiredIfRulePass(): void
    {
        echo "\n[测试] RequiredIf 规则 - 应该通过\n";
        echo "数据: type => 'person', id_card => '110101199001011234'\n";
        echo "规则: id_card => required_if:type,person\n";
        echo "期望: 通过，因为type=person时id_card必填且已提供\n";
        
        $container = $this->createContainerWithRequest([], [
            'type' => 'person',
            'id_card' => '110101199001011234',
        ]);
        
        $aspect = new ValidationAspect(
            $container,
            $container->get(\Hyperf\Validation\Contract\ValidatorFactoryInterface::class)
        );

        $joinPoint = $this->createMockJoinPoint([
            new RequestValidation(rules: [
                'type' => 'required|in:person,company',
                'id_card' => 'required_if:type,person',
            ])
        ]);

        $result = $aspect->process($joinPoint);
        echo "✅ 验证通过，返回: " . $result . "\n";
        $this->assertEquals('processed', $result);
    }

    /**
     * 测试条件必填规则 - required_if 失败
     */
    public function testRequiredIfRuleFails(): void
    {
        echo "\n[测试] RequiredIf 规则 - 应该失败\n";
        echo "数据: type => 'person', id_card => (未提供)\n";
        echo "规则: id_card => required_if:type,person\n";
        echo "期望: 失败，因为type=person时id_card必填但未提供\n";
        
        $this->expectException(ValidateException::class);
        
        $container = $this->createContainerWithRequest([], [
            'type' => 'person',
        ]);
        
        $aspect = new ValidationAspect(
            $container,
            $container->get(\Hyperf\Validation\Contract\ValidatorFactoryInterface::class)
        );

        $joinPoint = $this->createMockJoinPoint([
            new RequestValidation(rules: [
                'type' => 'required|in:person,company',
                'id_card' => 'required_if:type,person',
            ])
        ]);

        try {
            $aspect->process($joinPoint);
            echo "❌ 验证通过了（不应该）\n";
        } catch (ValidateException $e) {
            echo "✅ 验证失败: " . $e->getMessage() . "\n";
            throw $e;
        }
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
