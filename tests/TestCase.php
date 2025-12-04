<?php

declare(strict_types=1);

namespace HPlus\Validate\Tests;

use Hyperf\Context\ApplicationContext;
use Hyperf\Context\Context;
use Hyperf\Di\Container;
use Hyperf\Di\Definition\DefinitionSourceFactory;
use Hyperf\Contract\TranslatorInterface;
use Hyperf\Validation\Contract\ValidatorFactoryInterface;
use Hyperf\Validation\ValidatorFactory;
use Mockery;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

/**
 * 测试基类
 */
abstract class TestCase extends BaseTestCase
{
    protected ContainerInterface $container;

    protected function setUp(): void
    {
        parent::setUp();
        $this->container = $this->createContainer();
        ApplicationContext::setContainer($this->container);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        Context::destroy(ServerRequestInterface::class);
        
        // 清理 ValidationAspect 的缓存，避免测试之间相互影响
        if (class_exists(\HPlus\Validate\Aspect\ValidationAspect::class)) {
            \HPlus\Validate\Aspect\ValidationAspect::clearCache();
        }
        
        parent::tearDown();
    }

    /**
     * 创建容器
     */
    protected function createContainer(): ContainerInterface
    {
        $container = new Container(
            (new DefinitionSourceFactory())()
        );

        // Mock Translator
        $translator = Mockery::mock(TranslatorInterface::class);
        $translator->shouldReceive('trans')->andReturnUsing(function ($key, $replace = []) {
            // 提供一些常用的翻译
            $translations = [
                'validation.required' => ':attribute 字段是必需的',
                'validation.email' => ':attribute 必须是有效的电子邮件地址',
                'validation.string' => ':attribute 必须是字符串',
                'validation.integer' => ':attribute 必须是整数',
                'validation.min.string' => ':attribute 至少需要 :min 个字符',
                'validation.max.string' => ':attribute 不能超过 :max 个字符',
                'validation.between.numeric' => ':attribute 必须在 :min 到 :max 之间',
                'validation.in' => ':attribute 必须是以下之一: :values',
                'validation.confirmed' => ':attribute 确认不匹配',
                'validation.url' => ':attribute 必须是有效的 URL',
                'validation.boolean' => ':attribute 必须是布尔值',
                'validation.array' => ':attribute 必须是数组',
                'validation.regex' => ':attribute 格式不正确',
            ];
            
            $message = $translations[$key] ?? $key;
            
            // 替换占位符
            foreach ($replace as $placeholder => $value) {
                $message = str_replace(':' . $placeholder, (string) $value, $message);
            }
            
            return $message;
        });
        $translator->shouldReceive('get')->andReturnUsing(function ($key, $replace = []) {
            return $key;
        });
        
        $container->set(TranslatorInterface::class, $translator);

        // 注册验证器工厂
        $validatorFactory = new ValidatorFactory($translator, $container);
        $container->set(ValidatorFactoryInterface::class, $validatorFactory);

        return $container;
    }

    /**
     * 创建 Mock 请求
     */
    protected function createMockRequest(
        array $queryParams = [],
        array $bodyParams = [],
        string $method = 'POST',
        string $contentType = 'application/json'
    ): ServerRequestInterface {
        $request = Mockery::mock(ServerRequestInterface::class);
        
        $request->shouldReceive('getQueryParams')
            ->andReturn($queryParams);
        
        $request->shouldReceive('getParsedBody')
            ->andReturn($bodyParams);
        
        $request->shouldReceive('getMethod')
            ->andReturn($method);
        
        $request->shouldReceive('getHeaderLine')
            ->with('Content-Type')
            ->andReturn($contentType);

        // Mock URI
        $uri = Mockery::mock(UriInterface::class);
        $uri->shouldReceive('getPath')->andReturn('/test');
        $request->shouldReceive('getUri')->andReturn($uri);

        return $request;
    }

    /**
     * 设置请求到上下文
     */
    protected function setRequestContext(ServerRequestInterface $request): void
    {
        Context::set(ServerRequestInterface::class, $request);
    }

    /**
     * 创建带请求的容器
     */
    protected function createContainerWithRequest(
        array $queryParams = [],
        array $bodyParams = [],
        string $method = 'POST'
    ): ContainerInterface {
        $container = $this->createContainer();
        
        $request = $this->createMockRequest($queryParams, $bodyParams, $method);
        $this->setRequestContext($request);
        
        // 直接在容器中注册 Request 实例，而不是 Closure
        $container->set(ServerRequestInterface::class, $request);
        
        return $container;
    }

    /**
     * 断言验证异常
     */
    protected function assertValidationException(
        callable $callback,
        ?string $expectedMessage = null
    ): void {
        $this->expectException(\HPlus\Validate\Exception\ValidateException::class);
        
        if ($expectedMessage !== null) {
            $this->expectExceptionMessage($expectedMessage);
        }
        
        $callback();
    }
}
