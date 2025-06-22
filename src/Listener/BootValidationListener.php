<?php

declare(strict_types=1);

namespace HPlus\Validate\Listener;

use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Framework\Event\BootApplication;
use Hyperf\Di\Annotation\AnnotationCollector;
use HPlus\Validate\Annotations\RequestValidation;
use HPlus\Validate\Annotations\Validation;
use HPlus\Validate\RuleParser;
use Psr\Container\ContainerInterface;

/**
 * 验证器启动监听器
 * 在应用启动时预加载所有验证规则
 */
class BootValidationListener implements ListenerInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function listen(): array
    {
        return [
            BootApplication::class,
        ];
    }

    public function process(object $event)
    {
        $startTime = microtime(true);
        $ruleCount = 0;
        
        // 预加载 RequestValidation 注解
        $requestValidations = AnnotationCollector::getClassesByAnnotation(RequestValidation::class);
        foreach ($requestValidations as $class => $annotation) {
            $methods = AnnotationCollector::getClassMethodsByAnnotation($class, RequestValidation::class);
            foreach ($methods as $method => $annotations) {
                foreach ($annotations as $annotation) {
                    if ($annotation instanceof RequestValidation && !empty($annotation->rules)) {
                        // 预解析规则
                        RuleParser::rulesToJsonSchema($annotation->rules);
                        $ruleCount += count($annotation->rules);
                    }
                }
            }
        }
        
        // 预加载 Validation 注解
        $validations = AnnotationCollector::getClassesByAnnotation(Validation::class);
        foreach ($validations as $class => $annotation) {
            $methods = AnnotationCollector::getClassMethodsByAnnotation($class, Validation::class);
            foreach ($methods as $method => $annotations) {
                foreach ($annotations as $annotation) {
                    if ($annotation instanceof Validation && !empty($annotation->rules)) {
                        // 预解析规则
                        RuleParser::rulesToJsonSchema($annotation->rules);
                        $ruleCount += count($annotation->rules);
                    }
                }
            }
        }
        
        // 预热常用规则
        $this->warmupCommonRules();
        
        $duration = round((microtime(true) - $startTime) * 1000, 2);
        $cacheStats = RuleParser::getCacheStats();
        
        echo sprintf(
            "[Validation] 预加载完成: %d 条规则, 耗时 %sms, 缓存命中率 %s\n",
            $ruleCount,
            $duration,
            $cacheStats['hit_rate'] ?? '0%'
        );
    }
    
    /**
     * 预热常用验证规则
     */
    private function warmupCommonRules(): void
    {
        $commonRules = [
            // 基础类型
            'required|string|max:255',
            'required|integer|min:1',
            'required|numeric|between:0,100',
            'required|boolean',
            'required|array',
            'nullable|string',
            
            // 常用格式
            'required|email|max:255',
            'required|mobile',
            'required|url|max:500',
            'required|date',
            'required|date_format:Y-m-d H:i:s',
            
            // 文件验证
            'nullable|file|mimes:jpg,png,gif|max:2048',
            'required|file|mimes:pdf,doc,docx|max:10240',
            
            // 复杂规则
            'required|string|regex:/^[a-zA-Z0-9_]+$/',
            'required|confirmed',
            'required_if:type,business',
            'required_with:password',
        ];
        
        foreach ($commonRules as $rule) {
            RuleParser::ruleToJsonSchema($rule);
        }
    }
} 