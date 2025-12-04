<?php

declare(strict_types=1);

namespace HPlus\Validate\Aspect;

use HPlus\Validate\Annotations\RequestValidation;
use HPlus\Validate\Exception\ValidateException;
use Hyperf\Context\Context;
use Hyperf\Di\Annotation\Aspect;
use Hyperf\Di\Aop\AbstractAspect;
use Hyperf\Di\Aop\ProceedingJoinPoint;
use Hyperf\Validation\Contract\ValidatorFactoryInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * 验证切面 - 基于 hyperf/validation
 */
#[Aspect]
class ValidationAspect extends AbstractAspect
{
    public array $annotations = [RequestValidation::class];

    /**
     * 注解规则缓存（常驻内存，减少反射开销）
     */
    private static array $ruleCache = [];

    /**
     * 缓存统计
     */
    private static array $cacheStats = [
        'hits' => 0,
        'misses' => 0,
        'total' => 0,
    ];

    public function __construct(
        protected ContainerInterface $container,
        protected ValidatorFactoryInterface $validatorFactory
    ) {
    }

    /**
     * 切面处理
     */
    public function process(ProceedingJoinPoint $proceedingJoinPoint)
    {
        $className = $proceedingJoinPoint->className;
        $methodName = $proceedingJoinPoint->methodName;
        $cacheKey = $className . '@' . $methodName;

        self::$cacheStats['total']++;

        // 从缓存获取验证配置
        if (!isset(self::$ruleCache[$cacheKey])) {
            self::$cacheStats['misses']++;
            self::$ruleCache[$cacheKey] = $this->parseValidationConfig($proceedingJoinPoint);
        } else {
            self::$cacheStats['hits']++;
        }

        $config = self::$ruleCache[$cacheKey];

        // 如果没有验证规则，直接执行
        if (empty($config)) {
            return $proceedingJoinPoint->process();
        }

        // 执行验证
        $this->validate($config);

        return $proceedingJoinPoint->process();
    }

    /**
     * 解析验证配置（仅首次执行）
     */
    private function parseValidationConfig(ProceedingJoinPoint $proceedingJoinPoint): ?array
    {
        foreach ($proceedingJoinPoint->getAnnotationMetadata()->method as $annotation) {
            if ($annotation instanceof RequestValidation) {
                return [
                    'rules' => $annotation->rules,
                    'messages' => $annotation->messages,
                    'attributes' => $annotation->attributes,
                    'mode' => $annotation->mode,
                    'filter' => $annotation->filter,
                    'security' => $annotation->security,
                    'stopOnFirstFailure' => $annotation->stopOnFirstFailure,
                ];
            }
        }

        return null;
    }

    /**
     * 执行验证
     */
    private function validate(array $config): void
    {
        $request = $this->container->get(ServerRequestInterface::class);
        
        // 根据模式获取数据
        $data = match ($config['mode']) {
            'json' => $request->getParsedBody() ?: [],
            'query' => $request->getQueryParams(),
            'all' => array_merge(
                $request->getQueryParams(),
                $request->getParsedBody() ?: []
            ),
            default => $request->getParsedBody() ?: [],
        };

        // 获取规则中定义的字段列表
        $allowedFields = $this->getFieldsFromRules($config['rules']);

        // 安全模式：检查是否有未定义的字段
        if ($config['security']) {
            foreach (array_keys($data) as $key) {
                if (!in_array($key, $allowedFields, true)) {
                    throw new ValidateException("params {$key} invalid", 422);
                }
            }
        }

        // 使用 hyperf/validation 进行验证
        $validator = $this->validatorFactory->make(
            $data,
            $config['rules'],
            $config['messages'],
            $config['attributes']
        );

        // 设置是否在第一个失败时停止
        // 注意：Hyperf 3.1 的 Validator 不支持 stopOnFirstFailure() 方法
        // 但是可以通过配置项实现类似效果
        if ($config['stopOnFirstFailure'] && method_exists($validator, 'stopOnFirstFailure')) {
            $validator->stopOnFirstFailure();
        }

        // 验证失败抛出异常
        if ($validator->fails()) {
            $errors = $validator->errors();
            
            // 获取第一个错误消息
            $firstError = $errors->first();
            
            throw new ValidateException($firstError, 422);
        }

        // 过滤模式：只保留规则中定义的字段，更新到请求上下文
        if ($config['filter']) {
            $filteredData = array_intersect_key($data, array_flip($allowedFields));
            
            Context::override(ServerRequestInterface::class, function (ServerRequestInterface $request) use ($filteredData, $config) {
                return match ($config['mode']) {
                    'query' => $request->withQueryParams($filteredData),
                    default => $request->withParsedBody($filteredData),
                };
            });
        }
    }

    /**
     * 从规则中提取字段名列表
     */
    private function getFieldsFromRules(array $rules): array
    {
        $fields = [];
        foreach (array_keys($rules) as $field) {
            // 处理 field|description 格式
            if (str_contains($field, '|')) {
                $field = explode('|', $field)[0];
            }
            // 处理嵌套字段 field.* 或 field.sub
            $field = explode('.', $field)[0];
            
            if (!in_array($field, $fields, true)) {
                $fields[] = $field;
            }
        }
        return $fields;
    }

    /**
     * 获取缓存统计信息
     */
    public static function getCacheStats(): array
    {
        return [
            'hits' => self::$cacheStats['hits'],
            'misses' => self::$cacheStats['misses'],
            'total' => self::$cacheStats['total'],
            'hit_rate' => self::$cacheStats['total'] > 0
                ? round(self::$cacheStats['hits'] / self::$cacheStats['total'] * 100, 2) . '%'
                : '0%',
            'rule_cache_size' => count(self::$ruleCache),
        ];
    }

    /**
     * 清空缓存
     */
    public static function clearCache(): void
    {
        self::$ruleCache = [];
        self::$cacheStats = [
            'hits' => 0,
            'misses' => 0,
            'total' => 0,
        ];
    }
}