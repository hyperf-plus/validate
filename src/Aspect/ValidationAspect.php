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
<<<<<<< HEAD
    public array $annotations = [RequestValidation::class];

    /**
     * 注解规则缓存（常驻内存，减少反射开销）
=======
    /**
     * 验证规则缓存（常驻内存）
>>>>>>> 6490b4a99ecb2dc9d88003e0d659cdcb6a6dc610
     */
    private static array $ruleCache = [];



    /**
<<<<<<< HEAD
     * 缓存统计
=======
     * 类存在性检查缓存
     */
    private static array $classExistsCache = [];



    /**
     * 缓存统计信息
>>>>>>> 6490b4a99ecb2dc9d88003e0d659cdcb6a6dc610
     */
    private static array $cacheStats = [
        'hits' => 0,
        'misses' => 0,
        'total' => 0,
    ];

<<<<<<< HEAD
    public function __construct(
        protected ContainerInterface $container,
        protected ValidatorFactoryInterface $validatorFactory
=======


    /**
     * 请求对象（懒加载）
     */
    private ?ServerRequestInterface $request = null;

    // 要切入的类，可以多个，亦可通过 :: 标识到具体的某个方法，通过 * 可以模糊匹配
    public array $annotations = [Validation::class, RequestValidation::class];

    public function __construct(
        private ContainerInterface $container
>>>>>>> 6490b4a99ecb2dc9d88003e0d659cdcb6a6dc610
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
<<<<<<< HEAD
            self::$ruleCache[$cacheKey] = $this->parseValidationConfig($proceedingJoinPoint);
=======
            $rules = $this->parseValidationRules($proceedingJoinPoint);
            
            // 提前退出：如果没有验证规则，直接返回
            if (empty($rules)) {
                return $proceedingJoinPoint->process();
            }
            
            self::$ruleCache[$cacheKey] = $rules;
>>>>>>> 6490b4a99ecb2dc9d88003e0d659cdcb6a6dc610
        } else {
            self::$cacheStats['hits']++;
            $rules = self::$ruleCache[$cacheKey];
            
            // 提前退出：如果缓存的规则为空
            if (empty($rules)) {
                return $proceedingJoinPoint->process();
            }
        }

<<<<<<< HEAD
        $config = self::$ruleCache[$cacheKey];

        // 如果没有验证规则，直接执行
        if (empty($config)) {
            return $proceedingJoinPoint->process();
        }

        // 执行验证
        $this->validate($config);
=======
        // 执行验证
        foreach ($rules as $rule) {
            $this->executeValidation($rule, $proceedingJoinPoint);
        }
>>>>>>> 6490b4a99ecb2dc9d88003e0d659cdcb6a6dc610

        return $proceedingJoinPoint->process();
    }

    /**
     * 解析验证配置（仅首次执行）
     */
    private function parseValidationConfig(ProceedingJoinPoint $proceedingJoinPoint): ?array
    {
<<<<<<< HEAD
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
=======
        $annotations = $proceedingJoinPoint->getAnnotationMetadata()->method;
        
        // 提前退出：如果没有注解
        if (empty($annotations)) {
            return [];
        }

        $rules = [];
        foreach ($annotations as $validation) {
            switch (true) {
                case $validation instanceof RequestValidation:
                    // 提前退出：如果没有验证规则
                    if (empty($validation->rules)) {
                        continue 2;
                    }
                    
                    $rules[] = [
                        'type' => 'request',
                        'validation' => $validation,
                        'rules' => $validation->rules,
                        'scene' => $validation->scene ?: $proceedingJoinPoint->methodName,
                        'messages' => $validation->messages ?? [],
                        'attributes' => $validation->attributes ?? [],
                        'batch' => $validation->batch,
                        'security' => $validation->security,
                        'filter' => $validation->filter,
                    ];
                    break;

                case $validation instanceof Validation:
                    $rules[] = [
                        'type' => 'field',
                        'validation' => $validation,
                        'field' => $validation->field,
                        'rules' => $validation->rules,
                        'scene' => $validation->scene ?: $proceedingJoinPoint->methodName,
                        'validate' => $validation->validate,
                        'batch' => $validation->batch,
                        'security' => $validation->security,
                        'filter' => $validation->filter,
                    ];
                    break;
>>>>>>> 6490b4a99ecb2dc9d88003e0d659cdcb6a6dc610
            }
        }

        return null;
    }

    /**
     * 执行验证
     */
    private function validate(array $config): void
    {
<<<<<<< HEAD
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
=======
        switch ($rule['type']) {
            case 'request':
                $verData = $this->getRequestData();
                break;
            case 'field':
                $verData = $proceedingJoinPoint->arguments['keys'][$rule['field']] ?? null;
                break;
            default:
                return;
        }

        $this->validationData($rule, $verData, $proceedingJoinPoint);
    }

    /**
     * 获取请求数据
     */
    private function getRequestData(): array
    {
        $request = $this->getRequest();
        $queryParams = $request->getQueryParams();
        $bodyParams = $request->getParsedBody() ?: [];
        return array_merge($queryParams, $bodyParams);
    }

    /**
     * 懒加载获取请求对象
     */
    private function getRequest(): ServerRequestInterface
    {
        if ($this->request === null) {
            $this->request = $this->container->get(ServerRequestInterface::class);
        }
        return $this->request;
    }

    /**
     * 验证数据（优化版）
     */
    private function validationData(array $rule, $verData, ProceedingJoinPoint $proceedingJoinPoint): void
    {
        $validation = $rule['validation'];
        $isRequest = $rule['type'] === 'request';

        // 获取验证器实例
        $validate = $this->getValidator($rule);

        $rules = $rule['rules'] ?? null;

        // 处理自定义验证器类
        if ($rules === null && isset($rule['validate'])) {
            $class = $rule['validate'];
            if (!$this->classExistsCached($class)) {
                throw new ValidateException('class not exists:' . $class);
            }
            $rules = $validate->getSceneRule($rule['scene']);
        }

        // 设置自定义消息和属性（仅在需要时）
        if (!empty($rule['messages'])) {
            $validate->message($rule['messages']);
        }

        if (!empty($rule['attributes'])) {
            $validate->setAttributes($rule['attributes']);
        }

        // 执行验证
        if ($validate->batch($rule['batch'])->check($verData, $rules, $rule['scene']) === false) {
            throw new ValidateException((string)$validate->getError());
        }

        // 安全检查（仅在启用时）
        if ($rule['security']) {
            $fields = $this->getFields($rules);
            foreach ($verData as $key => $item) {
                if (!in_array($key, $fields, true)) {
                    throw new ValidateException('params ' . $key . ' invalid');
>>>>>>> 6490b4a99ecb2dc9d88003e0d659cdcb6a6dc610
                }
            }
        }

<<<<<<< HEAD
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
=======
        // 数据过滤（仅在启用时）
        if ($rule['filter']) {
            $fields = $this->getFields($rules);
            $filteredData = [];
            foreach ($verData as $key => $value) {
                if (in_array($key, $fields, true)) {
                    $filteredData[$key] = $value;
                }
            }

            if ($isRequest) {
                Context::override(ServerRequestInterface::class, function (ServerRequestInterface $request) use ($filteredData) {
                    return $request->withParsedBody($filteredData);
                });
            } else {
                $proceedingJoinPoint->arguments['keys'][$validation->field] = $filteredData;
            }
>>>>>>> 6490b4a99ecb2dc9d88003e0d659cdcb6a6dc610
        }
    }

    /**
<<<<<<< HEAD
     * 从规则中提取字段名列表
     */
    private function getFieldsFromRules(array $rules): array
=======
     * 获取验证器实例
     */
    private function getValidator(array $rule): Validate
    {
        if (isset($rule['validate']) && $this->classExistsCached($rule['validate'])) {
            $class = $rule['validate'];
            return new $class();
        }
        
        return new Validate();
    }

    /**
     * 类存在性检查（带缓存）
     */
    private function classExistsCached(string $className): bool
    {
        if (isset(self::$classExistsCache[$className])) {
            return self::$classExistsCache[$className];
        }

        $exists = class_exists($className);
        self::$classExistsCache[$className] = $exists;
        return $exists;
    }

    /**
     * 获取字段列表
     */
    private function getFields(array $rules): array
>>>>>>> 6490b4a99ecb2dc9d88003e0d659cdcb6a6dc610
    {
        $fields = [];
        foreach (array_keys($rules) as $field) {
            // 处理 field|description 格式
            if (str_contains($field, '|')) {
                $field = explode('|', $field)[0];
            }
<<<<<<< HEAD
            // 处理嵌套字段 field.* 或 field.sub
            $field = explode('.', $field)[0];
            
            if (!in_array($field, $fields, true)) {
                $fields[] = $field;
=======
            if (strpos($field, '|') !== false) {
                // 字段|描述 用于指定属性名称
                [$field] = explode('|', $field, 2);
>>>>>>> 6490b4a99ecb2dc9d88003e0d659cdcb6a6dc610
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
            'rule_hits' => self::$cacheStats['hits'],
            'rule_misses' => self::$cacheStats['misses'],
            'total_requests' => self::$cacheStats['total'],
            'rule_hit_rate' => self::$cacheStats['total'] > 0
                ? round(self::$cacheStats['hits'] / self::$cacheStats['total'] * 100, 2) . '%'
                : '0%',
            'rule_cache_size' => count(self::$ruleCache),
<<<<<<< HEAD
=======
            'class_cache_size' => count(self::$classExistsCache),
            'memory_usage' => round(memory_get_usage(true) / 1024 / 1024, 2) . 'MB',
>>>>>>> 6490b4a99ecb2dc9d88003e0d659cdcb6a6dc610
        ];
    }

    /**
     * 清空缓存
     */
    public static function clearCache(): void
    {
        self::$ruleCache = [];
<<<<<<< HEAD
=======
        self::$classExistsCache = [];
>>>>>>> 6490b4a99ecb2dc9d88003e0d659cdcb6a6dc610
        self::$cacheStats = [
            'hits' => 0,
            'misses' => 0,
            'total' => 0,
        ];
    }


}