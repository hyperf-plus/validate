<?php

declare(strict_types=1);

namespace HPlus\Validate\Tests\Cases;

use HPlus\Validate\Annotations\RequestValidation;
use HPlus\Validate\Annotations\Validation;
use HPlus\Validate\Validate;
use HPlus\Validate\ValidateRule;
use HPlus\Validate\Exception\ValidateException;

/**
 * 集成测试
 * 测试各组件的集成使用
 */
class IntegrationTest extends AbstractTestCase
{
    /**
     * 测试控制器注解验证
     */
    public function testControllerAnnotationValidation()
    {
        // 模拟一个带验证注解的控制器方法
        $controller = new class {
            #[RequestValidation(
                rules: [
                    'username' => 'required|string|min:3|max:20',
                    'email' => 'required|email',
                    'password' => 'required|string|min:6|confirmed',
                    'password_confirmation' => 'required'
                ],
                messages: [
                    'username.required' => '用户名不能为空',
                    'username.min' => '用户名至少3个字符',
                    'email.required' => '邮箱不能为空',
                    'email.email' => '邮箱格式不正确',
                    'password.required' => '密码不能为空',
                    'password.min' => '密码至少6个字符',
                    'password.confirmed' => '两次密码不一致'
                ]
            )]
            public function register()
            {
                return 'success';
            }
        };
        
        // 获取注解
        $reflection = new \ReflectionMethod($controller, 'register');
        $attributes = $reflection->getAttributes(RequestValidation::class);
        
        $this->assertNotEmpty($attributes);
        
        $annotation = $attributes[0]->newInstance();
        
        // 验证注解属性
        $this->assertIsArray($annotation->rules);
        $this->assertArrayHasKey('username', $annotation->rules);
        $this->assertArrayHasKey('email', $annotation->rules);
        $this->assertArrayHasKey('password', $annotation->rules);
        
        $this->assertIsArray($annotation->messages);
        $this->assertArrayHasKey('username.required', $annotation->messages);
    }
    
    /**
     * 测试参数注解验证
     */
    public function testParameterAnnotationValidation()
    {
        // 模拟带参数验证的方法
        $service = new class {
            public function updateProfile(
                #[Validation(
                    field: 'profile',
                    rules: [
                        'name' => 'required|string|max:50',
                        'bio' => 'nullable|string|max:500',
                        'avatar' => 'nullable|url',
                        'birthday' => 'nullable|date|before:today'
                    ]
                )]
                array $profile
            ) {
                return $profile;
            }
        };
        
        $reflection = new \ReflectionMethod($service, 'updateProfile');
        $parameters = $reflection->getParameters();
        
        $this->assertNotEmpty($parameters);
        
        $attributes = $parameters[0]->getAttributes(Validation::class);
        $this->assertNotEmpty($attributes);
        
        $annotation = $attributes[0]->newInstance();
        
        $this->assertEquals('profile', $annotation->field);
        $this->assertIsArray($annotation->rules);
        $this->assertArrayHasKey('name', $annotation->rules);
    }
    
    /**
     * 测试自定义验证器
     */
    public function testCustomValidator()
    {
        // 创建自定义验证器
        $validator = new class extends Validate {
            protected $rule = [
                'username' => 'required|unique:users',
                'email' => 'required|email|unique:users',
                'password' => 'required|min:6',
                'role' => 'required|in:admin,user,guest'
            ];
            
            protected $message = [
                'username.unique' => '用户名已存在',
                'email.unique' => '邮箱已被注册'
            ];
            
            protected $scene = [
                'login' => ['username', 'password'],
                'register' => ['username', 'email', 'password', 'role']
            ];
            
            // 自定义验证规则
            protected function unique($value, $rule, $data, $field)
            {
                // 模拟数据库查询
                $existingUsers = ['admin', 'test'];
                return !in_array($value, $existingUsers);
            }
        };
        
        // 测试登录场景
        $loginData = [
            'username' => 'newuser',
            'password' => 'password123'
        ];
        
        $result = $validator->scene('login')->check($loginData);
        $this->assertTrue($result);
        
        // 测试注册场景（用户名已存在）
        $registerData = [
            'username' => 'admin', // 已存在
            'email' => 'new@example.com',
            'password' => 'password123',
            'role' => 'user'
        ];
        
        $result = $validator->scene('register')->check($registerData);
        $this->assertFalse($result);
        $this->assertEquals('用户名已存在', $validator->getError());
    }
    
    /**
     * 测试ValidateRule助手类
     */
    public function testValidateRuleHelper()
    {
        // 使用ValidateRule构建规则
        $rule = ValidateRule::make()
            ->required()
            ->string()
            ->min(3)
            ->max(20)
            ->alphaDash()
            ->toString();
            
        $this->assertEquals('required|string|min:3|max:20|alphaDash', $rule);
        
        // 测试数字规则
        $numberRule = ValidateRule::make()
            ->required()
            ->integer()
            ->between(1, 100)
            ->toString();
            
        $this->assertEquals('required|integer|between:1,100', $numberRule);
        
        // 测试条件规则
        $conditionalRule = ValidateRule::make()
            ->requireIf('type', 'company')
            ->string()
            ->max(100)
            ->toString();
            
        $this->assertEquals('requireIf:type,company|string|max:100', $conditionalRule);
    }
    
    /**
     * 测试复杂嵌套验证
     */
    public function testComplexNestedValidation()
    {
        $validate = new Validate();
        
        // 复杂的订单数据结构
        $orderData = [
            'order_no' => 'ORD20231225001',
            'customer' => [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'phone' => '13800138000'
            ],
            'items' => [
                [
                    'product_id' => 1,
                    'name' => 'Product 1',
                    'quantity' => 2,
                    'price' => 99.99
                ],
                [
                    'product_id' => 2,
                    'name' => 'Product 2',
                    'quantity' => 1,
                    'price' => 199.99
                ]
            ],
            'shipping_address' => [
                'street' => '123 Main St',
                'city' => 'Beijing',
                'postal_code' => '100000',
                'country' => 'China'
            ],
            'payment' => [
                'method' => 'credit_card',
                'amount' => 399.97
            ]
        ];
        
        // 定义验证规则
        $rules = [
            'order_no' => 'required|regex:/^ORD[0-9]{11}$/',
            'customer.name' => 'required|string|max:50',
            'customer.email' => 'required|email',
            'customer.phone' => 'required|mobile',
            'items' => 'required|array|min:1',
            'shipping_address.street' => 'required|string',
            'shipping_address.city' => 'required|string',
            'shipping_address.postal_code' => 'required|regex:/^[0-9]{6}$/',
            'shipping_address.country' => 'required|in:China,USA,UK',
            'payment.method' => 'required|in:credit_card,paypal,alipay,wechat',
            'payment.amount' => 'required|numeric|gt:0'
        ];
        
        // 执行验证
        $result = $validate->check($orderData, $rules);
        $this->assertTrue($result);
        
        // 测试无效数据
        $invalidOrderData = $orderData;
        $invalidOrderData['customer']['email'] = 'invalid-email';
        $invalidOrderData['shipping_address']['postal_code'] = '12345'; // 应该是6位
        
        $result = $validate->check($invalidOrderData, $rules);
        $this->assertFalse($result);
    }
    
    /**
     * 测试验证异常处理
     */
    public function testValidationExceptionHandling()
    {
        try {
            $validate = new Validate();
            $validate->check(
                ['email' => 'invalid'],
                ['email' => 'required|email']
            );
            
            $this->fail('应该抛出验证异常');
        } catch (ValidateException $e) {
            $this->assertInstanceOf(ValidateException::class, $e);
            $this->assertNotEmpty($e->getMessage());
            
            // 测试异常的额外方法（如果有）
            if (method_exists($e, 'getErrors')) {
                $errors = $e->getErrors();
                $this->assertIsArray($errors);
            }
            
            if (method_exists($e, 'getField')) {
                $field = $e->getField();
                $this->assertEquals('email', $field);
            }
        }
    }
    
    /**
     * 测试与Hyperf集成
     */
    public function testHyperfIntegration()
    {
        // 模拟Hyperf请求验证
        $requestData = [
            'page' => '1',
            'per_page' => '20',
            'sort' => 'created_at',
            'order' => 'desc',
            'filters' => [
                'status' => 'active',
                'date_from' => '2023-01-01',
                'date_to' => '2023-12-31'
            ]
        ];
        
        $rules = [
            'page' => 'integer|min:1',
            'per_page' => 'integer|between:10,100',
            'sort' => 'in:id,name,created_at,updated_at',
            'order' => 'in:asc,desc',
            'filters.status' => 'in:active,inactive,pending',
            'filters.date_from' => 'date|date_format:Y-m-d',
            'filters.date_to' => 'date|date_format:Y-m-d|after:filters.date_from'
        ];
        
        $validate = new Validate();
        $result = $validate->check($requestData, $rules);
        
        $this->assertTrue($result);
    }
    
    /**
     * 测试性能监控集成
     */
    public function testPerformanceMonitoring()
    {
        // 启用性能监控
        $validate = new Validate();
        
        // 执行多次验证
        for ($i = 0; $i < 100; $i++) {
            $validate->check(
                ['test' => 'value' . $i],
                ['test' => 'required|string']
            );
        }
        
        // 获取性能统计（如果实现了相关功能）
        if (method_exists($validate, 'getStats')) {
            $stats = $validate->getStats();
            
            $this->assertArrayHasKey('total_validations', $stats);
            $this->assertArrayHasKey('avg_time', $stats);
            $this->assertArrayHasKey('total_time', $stats);
            
            echo "\n验证性能统计:\n";
            echo "总验证次数: " . $stats['total_validations'] . "\n";
            echo "平均耗时: " . $stats['avg_time'] . "ms\n";
            echo "总耗时: " . $stats['total_time'] . "ms\n";
        }
    }
} 