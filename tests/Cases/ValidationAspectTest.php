<?php

declare(strict_types=1);

namespace HPlus\Validate\Tests\Cases;

use HPlus\Validate\Aspect\ValidationAspect;
use HPlus\Validate\Annotations\RequestValidation;
use HPlus\Validate\Annotations\Validation;
use HPlus\Validate\Exception\ValidateException;
use Hyperf\Di\Aop\ProceedingJoinPoint;
use Hyperf\Di\Annotation\AnnotationCollector;
use Hyperf\Context\Context;
use Psr\Http\Message\ServerRequestInterface;
use Mockery;

/**
 * 验证切面测试
 * @covers \HPlus\Validate\Aspect\ValidationAspect
 */
class ValidationAspectTest extends AbstractTestCase
{
    protected ValidationAspect $aspect;
    protected $container;
    protected $request;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->container = Mockery::mock(\Psr\Container\ContainerInterface::class);
        $this->request = Mockery::mock(ServerRequestInterface::class);
        
        $this->container->shouldReceive('get')
            ->with(ServerRequestInterface::class)
            ->andReturn($this->request);
            
        $this->aspect = new ValidationAspect($this->container, $this->request);
    }
    
    /**
     * 测试RequestValidation注解验证
     */
    public function testRequestValidation()
    {
        // 模拟请求数据
        $this->request->shouldReceive('all')->andReturn([
            'name' => 'test',
            'email' => 'test@example.com'
        ]);
        
        // 创建RequestValidation注解
        $annotation = new RequestValidation();
        $annotation->rules = [
            'name' => 'required|string',
            'email' => 'required|email'
        ];
        
        // 模拟ProceedingJoinPoint
        $proceedingJoinPoint = Mockery::mock(ProceedingJoinPoint::class);
        $proceedingJoinPoint->className = 'TestController';
        $proceedingJoinPoint->methodName = 'test';
        
        $proceedingJoinPoint->shouldReceive('getAnnotationMetadata')
            ->andReturn((object)['method' => [$annotation]]);
            
        $proceedingJoinPoint->shouldReceive('process')
            ->once()
            ->andReturn('success');
            
        // 执行验证
        $result = $this->aspect->process($proceedingJoinPoint);
        
        $this->assertEquals('success', $result);
    }
    
    /**
     * 测试验证失败抛出异常
     */
    public function testValidationFailure()
    {
        $this->expectException(ValidateException::class);
        
        // 模拟空数据
        $this->request->shouldReceive('all')->andReturn([]);
        
        $annotation = new RequestValidation();
        $annotation->rules = [
            'name' => 'required',
            'email' => 'required|email'
        ];
        
        $proceedingJoinPoint = Mockery::mock(ProceedingJoinPoint::class);
        $proceedingJoinPoint->className = 'TestController';
        $proceedingJoinPoint->methodName = 'test';
        
        $proceedingJoinPoint->shouldReceive('getAnnotationMetadata')
            ->andReturn((object)['method' => [$annotation]]);
            
        // 执行验证，应该抛出异常
        $this->aspect->process($proceedingJoinPoint);
    }
    
    /**
     * 测试Security模式
     */
    public function testSecurityMode()
    {
        $this->expectException(ValidateException::class);
        $this->expectExceptionMessage('params extra_field invalid');
        
        // 包含额外字段的数据
        $this->request->shouldReceive('all')->andReturn([
            'name' => 'test',
            'email' => 'test@example.com',
            'extra_field' => 'should not be here'
        ]);
        
        $annotation = new RequestValidation();
        $annotation->rules = [
            'name' => 'required',
            'email' => 'required|email'
        ];
        $annotation->security = true; // 开启security模式
        
        $proceedingJoinPoint = Mockery::mock(ProceedingJoinPoint::class);
        $proceedingJoinPoint->className = 'TestController';
        $proceedingJoinPoint->methodName = 'test';
        
        $proceedingJoinPoint->shouldReceive('getAnnotationMetadata')
            ->andReturn((object)['method' => [$annotation]]);
            
        $this->aspect->process($proceedingJoinPoint);
    }
    
    /**
     * 测试Filter模式
     */
    public function testFilterMode()
    {
        // 包含额外字段的数据
        $originalData = [
            'name' => 'test',
            'email' => 'test@example.com',
            'extra_field' => 'should be filtered'
        ];
        
        $this->request->shouldReceive('all')->andReturn($originalData);
        
        $annotation = new RequestValidation();
        $annotation->rules = [
            'name' => 'required',
            'email' => 'required|email'
        ];
        $annotation->filter = true; // 开启filter模式
        
        $proceedingJoinPoint = Mockery::mock(ProceedingJoinPoint::class);
        $proceedingJoinPoint->className = 'TestController';
        $proceedingJoinPoint->methodName = 'test';
        
        $proceedingJoinPoint->shouldReceive('getAnnotationMetadata')
            ->andReturn((object)['method' => [$annotation]]);
            
        $proceedingJoinPoint->shouldReceive('process')
            ->once()
            ->andReturn('success');
        
        // 设置Context以便验证过滤后的数据
        $filteredRequest = null;
        Context::set(ServerRequestInterface::class, $this->request);
        
        $result = $this->aspect->process($proceedingJoinPoint);
        
        $this->assertEquals('success', $result);
        // 注意：实际测试中需要验证Context中的请求数据是否被正确过滤
    }
    
    /**
     * 测试自定义错误消息
     */
    public function testCustomErrorMessages()
    {
        $this->expectException(ValidateException::class);
        $this->expectExceptionMessage('姓名不能为空');
        
        $this->request->shouldReceive('all')->andReturn([
            'email' => 'test@example.com'
        ]);
        
        $annotation = new RequestValidation();
        $annotation->rules = ['name' => 'required'];
        $annotation->messages = ['name.required' => '姓名不能为空'];
        
        $proceedingJoinPoint = Mockery::mock(ProceedingJoinPoint::class);
        $proceedingJoinPoint->className = 'TestController';
        $proceedingJoinPoint->methodName = 'test';
        
        $proceedingJoinPoint->shouldReceive('getAnnotationMetadata')
            ->andReturn((object)['method' => [$annotation]]);
            
        $this->aspect->process($proceedingJoinPoint);
    }
    
    /**
     * 测试字段属性名称
     */
    public function testFieldAttributes()
    {
        $this->expectException(ValidateException::class);
        $this->expectExceptionMessage('用户名'); // 应该使用属性名而不是字段名
        
        $this->request->shouldReceive('all')->andReturn([]);
        
        $annotation = new RequestValidation();
        $annotation->rules = ['user_name' => 'required'];
        $annotation->attributes = ['user_name' => '用户名'];
        
        $proceedingJoinPoint = Mockery::mock(ProceedingJoinPoint::class);
        $proceedingJoinPoint->className = 'TestController';
        $proceedingJoinPoint->methodName = 'test';
        
        $proceedingJoinPoint->shouldReceive('getAnnotationMetadata')
            ->andReturn((object)['method' => [$annotation]]);
            
        $this->aspect->process($proceedingJoinPoint);
    }
    
    /**
     * 测试缓存功能
     */
    public function testCaching()
    {
        // 清空缓存
        ValidationAspect::clearCache();
        
        // 获取初始统计
        $stats = ValidationAspect::getCacheStats();
        $this->assertEquals(0, $stats['total']);
        $this->assertEquals(0, $stats['hits']);
        $this->assertEquals(0, $stats['misses']);
        
        // 第一次请求（缓存未命中）
        $this->request->shouldReceive('all')->andReturn(['name' => 'test']);
        
        $annotation = new RequestValidation();
        $annotation->rules = ['name' => 'required'];
        
        $proceedingJoinPoint = Mockery::mock(ProceedingJoinPoint::class);
        $proceedingJoinPoint->className = 'TestController';
        $proceedingJoinPoint->methodName = 'test';
        
        $proceedingJoinPoint->shouldReceive('getAnnotationMetadata')
            ->andReturn((object)['method' => [$annotation]]);
            
        $proceedingJoinPoint->shouldReceive('process')
            ->andReturn('success');
            
        $this->aspect->process($proceedingJoinPoint);
        
        // 检查缓存统计
        $stats = ValidationAspect::getCacheStats();
        $this->assertEquals(1, $stats['total']);
        $this->assertEquals(0, $stats['hits']);
        $this->assertEquals(1, $stats['misses']);
        
        // 第二次请求（缓存命中）
        $this->aspect->process($proceedingJoinPoint);
        
        $stats = ValidationAspect::getCacheStats();
        $this->assertEquals(2, $stats['total']);
        $this->assertEquals(1, $stats['hits']);
        $this->assertEquals(1, $stats['misses']);
        $this->assertEquals('50%', $stats['hit_rate']);
    }
    
    /**
     * 测试参数验证（Validation注解）
     */
    public function testParameterValidation()
    {
        $annotation = new Validation();
        $annotation->field = 'userData';
        $annotation->rules = [
            'name' => 'required|string',
            'age' => 'required|integer|min:18'
        ];
        
        $proceedingJoinPoint = Mockery::mock(ProceedingJoinPoint::class);
        $proceedingJoinPoint->className = 'TestController';
        $proceedingJoinPoint->methodName = 'test';
        $proceedingJoinPoint->arguments = [
            'keys' => [
                'userData' => [
                    'name' => 'John',
                    'age' => 25
                ]
            ]
        ];
        
        $proceedingJoinPoint->shouldReceive('getAnnotationMetadata')
            ->andReturn((object)['method' => [$annotation]]);
            
        $proceedingJoinPoint->shouldReceive('process')
            ->once()
            ->andReturn('success');
            
        $result = $this->aspect->process($proceedingJoinPoint);
        
        $this->assertEquals('success', $result);
    }
    
    /**
     * 测试场景验证
     */
    public function testSceneValidation()
    {
        $this->request->shouldReceive('all')->andReturn([
            'username' => 'test',
            'password' => 'password123'
        ]);
        
        $annotation = new RequestValidation();
        $annotation->validate = UserValidator::class; // 假设有自定义验证器
        $annotation->scene = 'login';
        
        // 这里需要模拟自定义验证器的行为
        // 实际测试时需要创建真实的验证器类
    }
} 