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
use Hyperf\Validation\Request\FormRequest;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * 请求验证切面
 */
#[Aspect]
class ValidationAspect extends AbstractAspect
{
    public array $annotations = [RequestValidation::class];

    private static array $configCache = [];
    private static array $formRequestCache = [];
    private static array $fieldsCache = [];
    /**
     * 内置中文错误消息（当未提供 messages 且本地无翻译文件时兜底）
     */
    private static array $builtinMessages = [
        'required' => ':attribute 为必填项。',
        'required_if' => '当 :other 为 :value 时，:attribute 不能为空。',
        'required_unless' => '当 :other 不为 :value 时，:attribute 不能为空。',
        'required_with' => '当 :values 存在时，:attribute 不能为空。',
        'required_with_all' => '当 :values 都存在时，:attribute 不能为空。',
        'required_without' => '当 :values 不存在时，:attribute 不能为空。',
        'required_without_all' => '当 :values 都不存在时，:attribute 不能为空。',
        'email' => ':attribute 必须是有效的电子邮件地址。',
        'phone' => ':attribute 必须是有效的手机号。',
        'mobile' => ':attribute 必须是有效的手机号。',
        'integer' => ':attribute 必须是整数。',
        'numeric' => ':attribute 必须是数字。',
        'string' => ':attribute 必须是字符串。',
        'array' => ':attribute 必须是数组。',
        'boolean' => ':attribute 必须是布尔值。',
        'url' => ':attribute 必须是有效的 URL。',
        'date' => ':attribute 必须是有效的日期。',
        'ip' => ':attribute 必须是有效的 IP 地址。',
        'ipv4' => ':attribute 必须是有效的 IPv4 地址。',
        'ipv6' => ':attribute 必须是有效的 IPv6 地址。',
        'json' => ':attribute 必须是合法的 JSON 字符串。',
        'alpha' => ':attribute 只能包含字母。',
        'alpha_num' => ':attribute 只能包含字母和数字。',
        'alpha_dash' => ':attribute 只能包含字母、数字、破折号和下划线。',
        'min' => [
            'numeric' => ':attribute 不能小于 :min。',
            'string' => ':attribute 长度不能少于 :min 个字符。',
            'array' => ':attribute 元素个数不能少于 :min 个。',
        ],
        'max' => [
            'numeric' => ':attribute 不能大于 :max。',
            'string' => ':attribute 长度不能超过 :max 个字符。',
            'array' => ':attribute 元素个数不能超过 :max 个。',
        ],
        'between' => [
            'numeric' => ':attribute 必须在 :min 和 :max 之间。',
            'string' => ':attribute 长度必须在 :min 和 :max 之间。',
            'array' => ':attribute 元素个数必须在 :min 和 :max 之间。',
        ],
        'size' => [
            'numeric' => ':attribute 必须等于 :size。',
            'string' => ':attribute 长度必须为 :size 个字符。',
            'array' => ':attribute 元素个数必须为 :size 个。',
        ],
        'in' => ':attribute 的值不在允许范围内。',
        'not_in' => ':attribute 的值不在允许范围内。',
        'regex' => ':attribute 格式不正确。',
        'same' => ':attribute 与 :other 必须相同。',
        'different' => ':attribute 与 :other 必须不同。',
        'confirmed' => ':attribute 与确认字段不匹配。',
        'after' => ':attribute 必须在 :date 之后。',
        'before' => ':attribute 必须在 :date 之前。',
        'after_or_equal' => ':attribute 必须在 :date 当天或之后。',
        'before_or_equal' => ':attribute 必须在 :date 当天或之前。',
        'unique' => ':attribute 已被占用。',
        'exists' => ':attribute 不存在或无效。',
    ];

    public function __construct(
        private readonly ContainerInterface $container,
        private readonly ValidatorFactoryInterface $validatorFactory
    ) {
    }

    public function process(ProceedingJoinPoint $proceedingJoinPoint): mixed
    {
        $cacheKey = $proceedingJoinPoint->className . '::' . $proceedingJoinPoint->methodName;

        $config = self::$configCache[$cacheKey] ??= $this->parseConfig($proceedingJoinPoint);

        if ($config === null) {
            return $proceedingJoinPoint->process();
        }

        $this->validate($config);

        return $proceedingJoinPoint->process();
    }

    private function parseConfig(ProceedingJoinPoint $joinPoint): ?array
    {
        foreach ($joinPoint->getAnnotationMetadata()->method as $annotation) {
            if ($annotation instanceof RequestValidation) {
                return [
                    'validate' => $annotation->validate,
                    'scene' => $annotation->scene,
                    'rules' => $annotation->rules,
                    'queryRules' => $annotation->queryRules,
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

    private function validate(array $config): void
    {
        if ($config['validate'] !== '') {
            $this->validateWithFormRequest($config['validate'], $config['scene']);
            return;
        }

        if (!empty($config['rules']) || !empty($config['queryRules'])) {
            $this->validateInline($config);
        }
    }

    private function validateWithFormRequest(string $class, string $scene): void
    {
        if (!isset(self::$formRequestCache[$class])) {
            self::$formRequestCache[$class] = class_exists($class) 
                && is_subclass_of($class, FormRequest::class);
        }

        if (!self::$formRequestCache[$class]) {
            throw new ValidateException(
                "Invalid FormRequest: {$class} must extend " . FormRequest::class,
                500
            );
        }

        /** @var FormRequest $formRequest */
        $formRequest = $this->container->get($class);

        if ($scene !== '') {
            $formRequest->scene($scene);
        }

        $formRequest->validateResolved();
    }

    private function validateInline(array $config): void
    {
        $request = $this->container->get(ServerRequestInterface::class);

        $filteredQuery = null;
        $filteredBody = null;

        if (!empty($config['queryRules'])) {
            $queryData = $request->getQueryParams();
            $filteredQuery = $this->validateData($queryData, $config['queryRules'], $config, 'query');
        }

        if (!empty($config['rules'])) {
            $bodyData = $this->getBodyData($request, $config['mode']);
            $filteredBody = $this->validateData($bodyData, $config['rules'], $config, 'body');
        }

        if ($config['filter'] && ($filteredQuery !== null || $filteredBody !== null)) {
            Context::override(ServerRequestInterface::class, static function (ServerRequestInterface $req) use ($filteredQuery, $filteredBody) {
                if ($filteredQuery !== null) {
                    $req = $req->withQueryParams($filteredQuery);
                }
                if ($filteredBody !== null) {
                    $req = $req->withParsedBody($filteredBody);
                }
                return $req;
            });
        }
    }

    private function validateData(array $data, array $rules, array $config, string $type): ?array
    {
        // 规范化规则：支持 "field|标题" 写法，将标题写入 attributes，并去掉规则 key 中的标题部分
        [$normalizedRules, $normalizedAttributes] = $this->normalizeRules($rules, $config['attributes']);

        $allowedFields = $this->getFieldsFromRules($normalizedRules);

        if ($config['security']) {
            $allowedSet = array_flip($allowedFields);
            foreach ($data as $key => $value) {
                if (!isset($allowedSet[$key])) {
                    throw new ValidateException("{$type} params {$key} invalid", 422);
                }
            }
        }

        // 合并内置中文消息（最低优先级），用户自定义 messages 覆盖内置
        $messages = $config['messages'] === []
            ? self::$builtinMessages
            : array_replace_recursive(self::$builtinMessages, $config['messages']);

        $validator = $this->validatorFactory->make($data, $normalizedRules, $messages, $normalizedAttributes);

        if ($config['stopOnFirstFailure'] && method_exists($validator, 'stopOnFirstFailure')) {
            $validator->stopOnFirstFailure();
        }

        if ($validator->fails()) {
            throw new ValidateException($validator->errors()->first(), 422);
        }

        return $config['filter'] ? array_intersect_key($data, array_flip($allowedFields)) : null;
    }

    private function getFieldsFromRules(array $rules): array
    {
        $cacheKey = md5(serialize(array_keys($rules)));

        if (isset(self::$fieldsCache[$cacheKey])) {
            return self::$fieldsCache[$cacheKey];
        }

        $fields = [];
        foreach (array_keys($rules) as $field) {
            $pos = strpos($field, '|');
            if ($pos !== false) {
                $field = substr($field, 0, $pos);
            }
            $pos = strpos($field, '.');
            if ($pos !== false) {
                $field = substr($field, 0, $pos);
            }
            $fields[$field] = true;
        }

        return self::$fieldsCache[$cacheKey] = array_keys($fields);
    }

    /**
     * 规范化规则：支持 "field|标题" 写法
     * - 去掉规则 key 中的标题部分
     * - 将标题写入 attributes（用户自定义 attributes 优先）
     *
     * @param array $rules 原始规则
     * @param array $attributes 已有的 attributes
     *
     * @return array{0: array, 1: array} [规范化后的规则, 合并后的 attributes]
     */
    private function normalizeRules(array $rules, array $attributes): array
    {
        $normalizedRules = [];
        foreach ($rules as $field => $rule) {
            $label = null;
            if (str_contains($field, '|')) {
                [$field, $label] = explode('|', $field, 2);
            }

            $normalizedRules[$field] = $rule;

            // 用户未提供 attributes 时，从 label 中填充
            if ($label !== null && !isset($attributes[$field])) {
                $attributes[$field] = $label;
            }
        }

        return [$normalizedRules, $attributes];
    }

    /**
     * 获取缓存统计（测试使用）
     */
    public static function getCacheStats(): array
    {
        return [
            'rule_cache_size' => count(self::$configCache),
            'form_request_cache_size' => count(self::$formRequestCache),
            'fields_cache_size' => count(self::$fieldsCache),
        ];
    }

    private function getBodyData(ServerRequestInterface $request, string $mode): array
    {
        return match ($mode) {
            'form' => $this->parseFormData($request),
            'xml' => $this->parseXmlData($request),
            default => (array) ($request->getParsedBody() ?? []),
        };
    }

    private function parseFormData(ServerRequestInterface $request): array
    {
        $data = $request->getParsedBody();
        if (is_array($data) && $data !== []) {
            return $data;
        }

        $body = $request->getBody();
        $content = (string) $body;

        if ($body->isSeekable()) {
            $body->rewind();
        }

        if ($content === '') {
            return [];
        }

        parse_str($content, $result);
        return $result;
    }

    private function parseXmlData(ServerRequestInterface $request): array
    {
        $body = $request->getBody();
        $content = (string) $body;

        if ($body->isSeekable()) {
            $body->rewind();
        }

        if ($content === '') {
            return [];
        }

        libxml_use_internal_errors(true);

        try {
            $xml = simplexml_load_string($content, 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_NONET);
            return $xml === false ? [] : (array) json_decode(json_encode($xml), true);
        } finally {
            libxml_clear_errors();
        }
    }

    /**
     * 清空缓存（测试用）
     */
    public static function clearCache(): void
    {
        self::$configCache = [];
        self::$formRequestCache = [];
        self::$fieldsCache = [];
    }
}
