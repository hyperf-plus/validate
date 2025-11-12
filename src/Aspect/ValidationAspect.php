<?php

declare (strict_types=1);

namespace HPlus\Validate\Aspect;

use HPlus\Validate\Annotations\RequestValidation;
use HPlus\Validate\Annotations\Validation;
use HPlus\Validate\Validate;
use HPlus\Validate\Exception\ValidateException;
use Hyperf\Di\Annotation\Aspect;
use Hyperf\Di\Aop\AbstractAspect;
use Hyperf\Di\Aop\ProceedingJoinPoint;
use Hyperf\Di\Exception\Exception;
use Hyperf\Context\Context;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;

#[Aspect]
class ValidationAspect extends AbstractAspect
{
    /**
     * 验证规则缓存（常驻内存）
     */
    private static array $ruleCache = [];



    /**
     * 类存在性检查缓存
     */
    private static array $classExistsCache = [];



    /**
     * 缓存统计信息
     */
    private static array $cacheStats = [
        'hits' => 0,
        'misses' => 0,
        'total' => 0,
    ];



    /**
     * 请求对象（懒加载）
     */
    private ?ServerRequestInterface $request = null;

    // 要切入的类，可以多个，亦可通过 :: 标识到具体的某个方法，通过 * 可以模糊匹配
    public array $annotations = [Validation::class, RequestValidation::class];

    public function __construct(
        private ContainerInterface $container
    ) {
    }

    /**
     * @param ProceedingJoinPoint $proceedingJoinPoint
     * @return mixed
     * @throws Exception
     * @throws ValidateException
     */
    public function process(ProceedingJoinPoint $proceedingJoinPoint)
    {
        $className = $proceedingJoinPoint->className;
        $methodName = $proceedingJoinPoint->methodName;
        $cacheKey = $className . '@' . $methodName;

        self::$cacheStats['total']++;

        // 从内存缓存获取验证规则
        if (!isset(self::$ruleCache[$cacheKey])) {
            self::$cacheStats['misses']++;
            $rules = $this->parseValidationRules($proceedingJoinPoint);
            
            // 提前退出：如果没有验证规则，直接返回
            if (empty($rules)) {
                return $proceedingJoinPoint->process();
            }
            
            self::$ruleCache[$cacheKey] = $rules;
        } else {
            self::$cacheStats['hits']++;
            $rules = self::$ruleCache[$cacheKey];
            
            // 提前退出：如果缓存的规则为空
            if (empty($rules)) {
                return $proceedingJoinPoint->process();
            }
        }

        // 执行验证
        foreach ($rules as $rule) {
            $this->executeValidation($rule, $proceedingJoinPoint);
        }

        return $proceedingJoinPoint->process();
    }

    /**
     * 解析验证规则（仅在首次请求时执行）
     */
    private function parseValidationRules(ProceedingJoinPoint $proceedingJoinPoint): array
    {
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
                        'dataType' => $validation->dataType ?? 'json',
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
            }
        }

        return $rules;
    }

    /**
     * 执行验证（使用缓存的规则）
     */
    private function executeValidation(array $rule, ProceedingJoinPoint $proceedingJoinPoint): void
    {
        switch ($rule['type']) {
            case 'request':
                $dataType = $rule['dataType'] ?? 'json';
                $verData = $this->getRequestData($dataType);
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
    private function getRequestData(string $dataType = 'json'): array
    {
        $request = $this->getRequest();
        $queryParams = $request->getQueryParams();
        
        // 根据 dataType 获取不同的请求体数据
        switch (strtolower($dataType)) {
            case 'xml':
                // XML 格式：从原始请求体中解析
                $bodyContent = $request->getBody()->getContents();
                if (!empty($bodyContent)) {
                    // 简单的 XML 解析（实际项目中建议使用专门的 XML 解析库）
                    libxml_use_internal_errors(true);
                    $xml = simplexml_load_string($bodyContent);
                    if ($xml !== false) {
                        $bodyParams = json_decode(json_encode($xml), true) ?: [];
                    } else {
                        $bodyParams = [];
                    }
                } else {
                    $bodyParams = [];
                }
                break;
                
            case 'form':
                // Form 表单格式：从 POST 数据获取
                $bodyParams = $request->getParsedBody() ?: [];
                // 如果 parsedBody 为空，尝试从 getBody 解析
                if (empty($bodyParams)) {
                    parse_str($request->getBody()->getContents(), $bodyParams);
                    $bodyParams = $bodyParams ?: [];
                }
                break;
                
            case 'json':
            default:
                // JSON 格式（默认）
                $bodyParams = $request->getParsedBody() ?: [];
                // 如果 parsedBody 为空，尝试从 getBody 解析 JSON
                if (empty($bodyParams)) {
                    $bodyContent = $request->getBody()->getContents();
                    if (!empty($bodyContent)) {
                        $decoded = json_decode($bodyContent, true);
                        if (json_last_error() === JSON_ERROR_NONE) {
                            $bodyParams = $decoded;
                        }
                    }
                }
                break;
        }
        
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
                }
            }
        }

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
        }
    }

    /**
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
    {
        $fields = [];
        foreach ($rules as $field => $rule) {
            if (is_numeric($field)) {
                $field = $rule;
            }
            if (strpos($field, '|') !== false) {
                // 字段|描述 用于指定属性名称
                [$field] = explode('|', $field, 2);
            }
            $fields[] = $field;
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
            'class_cache_size' => count(self::$classExistsCache),
            'memory_usage' => round(memory_get_usage(true) / 1024 / 1024, 2) . 'MB',
        ];
    }

    /**
     * 清空缓存（用于热更新）
     */
    public static function clearCache(): void
    {
        self::$ruleCache = [];
        self::$classExistsCache = [];
        self::$cacheStats = [
            'hits' => 0,
            'misses' => 0,
            'total' => 0,
        ];
    }


}