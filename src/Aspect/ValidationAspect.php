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
            $rules = self::$ruleCache[$cacheKey];
            
            // 提前退出：如果缓存的规则为空
            if (empty($rules)) {
                return $proceedingJoinPoint->process();
            }
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

    /**
     * 执行验证
     */
    private function validate(array $config): void
    {
        $request = $this->container->get(ServerRequestInterface::class);
        
        $filteredQueryData = null;
        $filteredBodyData = null;
        
        // 1. 验证查询参数（如果有 queryRules）
        if (!empty($config['queryRules'])) {
            $queryData = $request->getQueryParams();
            $allowedQueryFields = $this->getFieldsFromRules($config['queryRules']);
            
            // 安全模式：检查 query 是否有未定义的字段
            if ($config['security']) {
                foreach (array_keys($queryData) as $key) {
                    if (!in_array($key, $allowedQueryFields, true)) {
                        throw new ValidateException("query params {$key} invalid", 422);
                    }
                }
            }
            
            // 验证 query 参数
            $queryValidator = $this->validatorFactory->make(
                $queryData,
                $config['queryRules'],
                $config['messages'],
                $config['attributes']
            );
            
            if ($config['stopOnFirstFailure'] && method_exists($queryValidator, 'stopOnFirstFailure')) {
                $queryValidator->stopOnFirstFailure();
            }
            
            if ($queryValidator->fails()) {
                throw new ValidateException($queryValidator->errors()->first(), 422);
            }
            
            // 记录过滤后的 query 数据
            if ($config['filter']) {
                $filteredQueryData = array_intersect_key($queryData, array_flip($allowedQueryFields));
            }
        }
        
        // 2. 验证请求体（如果有 rules）
        if (!empty($config['rules'])) {
            // 根据 mode 解析请求体数据（json/form/xml）
            $bodyData = $this->getBodyData($request, $config['mode']);
            
            $allowedBodyFields = $this->getFieldsFromRules($config['rules']);
            
            // 安全模式：检查 body 是否有未定义的字段
            if ($config['security']) {
                foreach (array_keys($bodyData) as $key) {
                    if (!in_array($key, $allowedBodyFields, true)) {
                        throw new ValidateException("body params {$key} invalid", 422);
                    }
                }
            }
            
            // 验证请求体
            $bodyValidator = $this->validatorFactory->make(
                $bodyData,
                $config['rules'],
                $config['messages'],
                $config['attributes']
            );
            
            if ($config['stopOnFirstFailure'] && method_exists($bodyValidator, 'stopOnFirstFailure')) {
                $bodyValidator->stopOnFirstFailure();
            }
            
            if ($bodyValidator->fails()) {
                throw new ValidateException($bodyValidator->errors()->first(), 422);
            }
            
            // 记录过滤后的 body 数据
            if ($config['filter']) {
                $filteredBodyData = array_intersect_key($bodyData, array_flip($allowedBodyFields));
            }
        }
        
        // 3. 过滤模式：更新请求上下文
        if ($config['filter'] && ($filteredQueryData !== null || $filteredBodyData !== null)) {
            Context::override(ServerRequestInterface::class, function (ServerRequestInterface $request) use ($filteredQueryData, $filteredBodyData) {
                if ($filteredQueryData !== null) {
                    $request = $request->withQueryParams($filteredQueryData);
                }
                if ($filteredBodyData !== null) {
                    $request = $request->withParsedBody($filteredBodyData);
                }
                return $request;
            });
        }
    }

    /**
     * 根据模式获取请求体数据
     * @param string $mode 数据解析模式：json | form | xml
     */
    private function getBodyData(ServerRequestInterface $request, string $mode): array
    {
        return match ($mode) {
            'form' => $this->parseFormData($request),
            'xml' => $this->parseXmlData($request),
            default => $request->getParsedBody() ?: [], // json（默认）
        };
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
     * 解析表单数据 (application/x-www-form-urlencoded)
     */
    private function parseFormData(ServerRequestInterface $request): array
    {
        // 优先使用 getParsedBody（框架可能已解析）
        $data = $request->getParsedBody();
        if (!empty($data) && is_array($data)) {
            return $data;
        }
        
        // 手动解析原始请求体
        $body = $request->getBody();
        $content = (string) $body;
        
        // 重置流位置（如果可以）
        if ($body->isSeekable()) {
            $body->rewind();
        }
        
        if (empty($content)) {
            return [];
        }
        
        parse_str($content, $result);
        return $result ?: [];
    }

    /**
     * 解析 XML 数据
     */
    private function parseXmlData(ServerRequestInterface $request): array
    {
        // 检查 simplexml 扩展是否可用
        if (!function_exists('simplexml_load_string')) {
            throw new ValidateException(
                'XML mode requires the simplexml PHP extension. Please install it: pecl install simplexml or enable it in php.ini',
                422
            );
        }
        
        $body = $request->getBody();
        $content = (string) $body;
        
        // 重置流位置（如果可以）
        if ($body->isSeekable()) {
            $body->rewind();
        }
        
        if (empty($content)) {
            return [];
        }
        
        // PHP 8.0+ 默认禁用外部实体加载，此函数已弃用
        // PHP 8.0 以下版本需要手动禁用（安全考虑）
        $previousValue = null;
        if (PHP_VERSION_ID < 80000 && function_exists('libxml_disable_entity_loader')) {
            $previousValue = libxml_disable_entity_loader(true);
        }
        
        if (function_exists('libxml_use_internal_errors')) {
            libxml_use_internal_errors(true);
        }
        
        try {
            $xml = simplexml_load_string($content, 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_NONET);
            
            if ($xml === false) {
                // XML 解析失败，返回空数组
                return [];
            }
            
            // 将 SimpleXMLElement 转换为数组
            return $this->xmlToArray($xml);
        } finally {
            // 恢复原设置（仅 PHP 8.0 以下）
            if ($previousValue !== null && function_exists('libxml_disable_entity_loader')) {
                libxml_disable_entity_loader($previousValue);
            }
            if (function_exists('libxml_clear_errors')) {
                libxml_clear_errors();
            }
        }
    }

    /**
     * 将 SimpleXMLElement 转换为数组
     */
    private function xmlToArray(\SimpleXMLElement $xml): array
    {
        $result = [];
        
        foreach ($xml->children() as $key => $value) {
            $children = $value->children();
            
            if (count($children) > 0) {
                // 有子节点，递归处理
                $childArray = $this->xmlToArray($value);
                
                // 检查是否是同名元素（数组）
                if (isset($result[$key])) {
                    if (!is_array($result[$key]) || !isset($result[$key][0])) {
                        $result[$key] = [$result[$key]];
                    }
                    $result[$key][] = $childArray;
                } else {
                    $result[$key] = $childArray;
                }
            } else {
                // 叶子节点
                $val = (string) $value;
                
                // 检查是否是同名元素（数组）
                if (isset($result[$key])) {
                    if (!is_array($result[$key]) || !isset($result[$key][0])) {
                        $result[$key] = [$result[$key]];
                    }
                    $result[$key][] = $val;
                } else {
                    $result[$key] = $val;
                }
            }
        }
        
        return $result;
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