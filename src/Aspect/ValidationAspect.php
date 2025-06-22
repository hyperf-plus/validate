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
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var ServerRequestInterface
     */
    protected $request;

    /**
     * 验证规则缓存（常驻内存）
     */
    private static array $ruleCache = [];

    /**
     * 验证器实例缓存
     */
    private static array $validatorCache = [];

    /**
     * 缓存统计信息
     */
    private static array $cacheStats = [
        'hits' => 0,
        'misses' => 0,
        'total' => 0,
    ];

    // 要切入的类，可以多个，亦可通过 :: 标识到具体的某个方法，通过 * 可以模糊匹配
    public array $annotations = [Validation::class, RequestValidation::class];

    public function __construct(ContainerInterface $container, ServerRequestInterface $Request)
    {
        $this->container = $container;
        $this->request = $this->container->get(ServerRequestInterface::class);
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
            self::$ruleCache[$cacheKey] = $this->parseValidationRules($proceedingJoinPoint);
        } else {
            self::$cacheStats['hits']++;
        }

        $validationRules = self::$ruleCache[$cacheKey];

        // 执行验证
        foreach ($validationRules as $rule) {
            $this->executeValidation($rule, $proceedingJoinPoint);
        }

        return $proceedingJoinPoint->process();
    }

    /**
     * 解析验证规则（仅在首次请求时执行）
     */
    private function parseValidationRules(ProceedingJoinPoint $proceedingJoinPoint): array
    {
        $rules = [];

        foreach ($proceedingJoinPoint->getAnnotationMetadata()->method as $validation) {
            switch (true) {
                case $validation instanceof RequestValidation:
                    $rules[] = [
                        'type' => 'request',
                        'validation' => $validation,
                        'rules' => $validation->rules,
                        'scene' => $validation->scene ?: $proceedingJoinPoint->methodName,
                        'messages' => $validation->messages ?? [],
                        'attributes' => $validation->attributes ?? [],
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
        $validation = $rule['validation'];

        switch ($rule['type']) {
            case 'request':
                // 获取所有请求数据（GET + POST/PUT）
                $queryParams = $this->request->getQueryParams();
                $bodyParams = $this->request->getParsedBody() ?: [];
                $verData = array_merge($queryParams, $bodyParams);
                break;
            case 'field':
                $verData = $proceedingJoinPoint->arguments['keys'][$rule['field']] ?? null;
                break;
            default:
                return;
        }

        $this->validationData(
            $validation,
            $verData,
            $rule,
            $proceedingJoinPoint,
            $rule['type'] === 'request'
        );
    }

    /**
     * @param $validation
     * @param $verData
     * @param array $rule
     * @param $proceedingJoinPoint
     * @param $isRequest
     * @throws ValidateException
     */
    private function validationData($validation, $verData, array $rule, $proceedingJoinPoint, $isRequest = false)
    {
        /**
         * @var Validation $validation
         */
        /**
         * @var Validate $validate
         */

        // 使用缓存的验证器实例
        $validate = $this->getValidator($rule);

        $rules = $rule['rules'] ?? null;

        if ($rules === null && isset($rule['validate'])) {
            $class = $rule['validate'];
            if (!class_exists($class)) {
                throw new ValidateException('class not exists:' . $class);
            }
            $rules = $validate->getSceneRule($rule['scene']);
        }

        // 设置自定义消息和属性（如果有）
        if (!empty($rule['messages'])) {
            $validate->message($rule['messages']);
        }

        if (!empty($rule['attributes'])) {
            $validate->setAttributes($rule['attributes']);
        }

        if ($validate->batch($validation->batch)->check($verData, $rules, $rule['scene']) === false) {
            throw new ValidateException((string)$validate->getError());
        }

        if ($validation->security) {
            $fields = $this->getFields($rules);
            foreach ($verData as $key => $item) {
                if (!in_array($key, $fields)) {
                    throw new ValidateException('params ' . $key . ' invalid');
                }
            }
        }

        if ($validation->filter) {
            $fields = $this->getFields($rules);
            $verData = array_filter($verData, function ($value, $key) use ($fields) {
                return in_array($key, $fields);
            }, ARRAY_FILTER_USE_BOTH);

            switch ($isRequest) {
                case true:
                    Context::override(ServerRequestInterface::class, function (ServerRequestInterface $request) use ($verData) {
                        return $request->withParsedBody($verData);
                    });
                    break;
                default:
                    $proceedingJoinPoint->arguments['keys'][$validation->field] = $verData;
                    break;
            }
        }
    }

    /**
     * 获取验证器实例（使用缓存）
     */
    private function getValidator(array $rule): Validate
    {
        if (isset($rule['validate']) && class_exists($rule['validate'])) {
            $class = $rule['validate'];
            return new $class();
        }
        return  new Validate();
    }

    protected function getFields(array $rules)
    {
        $fields = [];
        foreach ($rules as $field => $rule) {
            if (is_numeric($field)) {
                $field = $rule;
            }
            if (strpos($field, '|')) {
                // 字段|描述 用于指定属性名称
                list($field,) = explode('|', $field);
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
            'hits' => self::$cacheStats['hits'],
            'misses' => self::$cacheStats['misses'],
            'total' => self::$cacheStats['total'],
            'hit_rate' => self::$cacheStats['total'] > 0
                ? round(self::$cacheStats['hits'] / self::$cacheStats['total'] * 100, 2) . '%'
                : '0%',
            'rule_cache_size' => count(self::$ruleCache),
            'validator_cache_size' => count(self::$validatorCache),
        ];
    }

    /**
     * 清空缓存（用于热更新）
     */
    public static function clearCache(): void
    {
        self::$ruleCache = [];
        self::$validatorCache = [];
        self::$cacheStats = [
            'hits' => 0,
            'misses' => 0,
            'total' => 0,
        ];
    }
}