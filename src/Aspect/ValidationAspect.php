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
        $allowedFields = $this->getFieldsFromRules($rules);

        if ($config['security']) {
            $allowedSet = array_flip($allowedFields);
            foreach ($data as $key => $value) {
                if (!isset($allowedSet[$key])) {
                    throw new ValidateException("{$type} params {$key} invalid", 422);
                }
            }
        }

        $validator = $this->validatorFactory->make($data, $rules, $config['messages'], $config['attributes']);

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

    /**
     * 获取缓存统计（测试用）
     */
    public static function getCacheStats(): array
    {
        return [
            'rule_hits' => 0,
            'rule_misses' => 0,
            'total_requests' => 0,
            'rule_hit_rate' => '0%',
            'rule_cache_size' => count(self::$configCache),
        ];
    }
}
