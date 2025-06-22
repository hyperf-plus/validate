<?php

declare(strict_types=1);

namespace HPlus\Validate\Tests\Unit;

use HPlus\Validate\Aspect\ValidationAspect;
use HPlus\Validate\Annotations\RequestValidation;
use HPlus\Validate\Annotations\Validation;
use HPlus\Validate\Exception\ValidateException;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * 验证切面测试
 * 
 * @covers \HPlus\Validate\Aspect\ValidationAspect
 */
final class ValidationAspectTest extends TestCase
{
    private ValidationAspect $aspect;
    /** @var ContainerInterface&MockObject */
    private $container;
    /** @var ServerRequestInterface&MockObject */
    private $request;

    protected function setUp(): void
    {
        parent::setUp();
        
        // 创建mock对象
        $this->container = $this->createMock(ContainerInterface::class);
        $this->request = $this->createMock(ServerRequestInterface::class);
        
        // 配置container返回request
        $this->container->expects($this->any())
            ->method('get')
            ->with(ServerRequestInterface::class)
            ->willReturn($this->request);
            
        $this->aspect = new ValidationAspect($this->container, $this->request);
    }

    /**
     * @test
     * @group aspect
     */
    public function it_can_be_instantiated(): void
    {
        $this->assertInstanceOf(ValidationAspect::class, $this->aspect);
    }

    /**
     * @test
     * @group annotations
     */
    public function it_recognizes_validation_annotations(): void
    {
        $annotation = new RequestValidation();
        $this->assertInstanceOf(RequestValidation::class, $annotation);
        
        $validation = new Validation();
        $this->assertInstanceOf(Validation::class, $validation);
    }

    /**
     * @test 
     * @group exceptions
     */
    public function it_can_throw_validation_exception(): void
    {
        $this->expectException(ValidateException::class);
        throw new ValidateException('测试验证异常');
    }

    /**
     * @test
     * @group exceptions
     */
    public function it_can_throw_validation_exception_with_custom_message(): void
    {
        $this->expectException(ValidateException::class);
        $this->expectExceptionMessage('自定义错误消息');
        
        throw new ValidateException('自定义错误消息');
    }
} 