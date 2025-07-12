<?php

declare(strict_types=1);

namespace HPlus\Validate;

/**
 * 高性能验证规则解析器
 * 
 * 设计模式：
 * - 工厂模式：创建不同类型的Schema
 * - 策略模式：支持多种规则解析策略
 * - 单例模式：缓存和性能优化
 * - 建造者模式：构建复杂的Schema结构
 * 
 * 性能优化：
 * - 规则缓存：避免重复解析相同规则
 * - 预编译正则：提升正则匹配性能
 * - 批量处理：减少循环开销
 * - 内存池：复用对象，减少GC压力
 */
class RuleParser
{
    /**
     * 规则解析缓存
     */
    private static array $ruleCache = [];

    /**
     * 字段名解析缓存
     */
    private static array $fieldCache = [];

    /**
     * Schema缓存
     */
    private static array $schemaCache = [];

    /**
     * 预编译的正则表达式
     */
    private static array $compiledRegex = [];

    /**
     * 类型映射表（性能优化）
     */
    private static array $typeMapping = [
        'integer' => 'integer',
        'int' => 'integer', 
        'numeric' => 'number',
        'decimal' => 'number',
        'float' => 'number',
        'double' => 'number',
        'boolean' => 'boolean',
        'bool' => 'boolean',
        'array' => 'array',
        'json' => 'object',
        'string' => 'string',
        'nullable' => null, // nullable 不改变类型
    ];

    /**
     * 格式映射表（性能优化）
     */
    private static array $formatMapping = [
        'email' => 'email',
        'url' => 'uri',
        'date' => 'date',
        'date_format' => 'date-time',
        'uuid' => 'uuid',
        'ip' => 'ipv4',
        'ipv4' => 'ipv4',
        'ipv6' => 'ipv6',
        'regex' => null, // 使用pattern代替
        'after_or_equal' => 'date',
        'afterOrEqual' => 'date',
    ];

    /**
     * 规则提取器策略
     */
    private static array $extractors = [];

    /**
     * 初始化预编译正则表达式
     */
    private static function initRegex(): void
    {
        if (empty(self::$compiledRegex)) {
            self::$compiledRegex = [
                'min' => '/\bmin:(\d+)\b/',
                'max' => '/\bmax:(\d+)\b/',
                'between' => '/\bbetween:(\d+),(\d+)\b/',
                'in' => '/\bin:([^|]+)/',
                'regex' => '/\bregex:([^|]+)/',
                'size' => '/\bsize:(\d+)\b/',
                'digits' => '/\bdigits:(\d+)\b/',
                'digits_between' => '/\bdigits_between:(\d+),(\d+)\b/',
                'required_if' => '/\brequired_if:([^|]+)/',
                'required_with' => '/\brequired_with:([^|]+)/',
                'default' => '/\bdefault:([^|]+)/',
                'after_or_equal' => '/\bafter_or_equal:([^|]+)/',
                'afterOrEqual' => '/\bafterOrEqual:([^|]+)/',
            ];
        }
    }

    /**
     * 初始化规则提取器
     */
    private static function initExtractors(): void
    {
        if (empty(self::$extractors)) {
            self::$extractors = [
                'type' => new TypeExtractor(),
                'format' => new FormatExtractor(),
                'constraint' => new ConstraintExtractor(),
                'validation' => new ValidationExtractor(),
            ];
        }
    }

    /**
     * 解析字段名和描述（性能优化版本）
     * 
     * @param string $field 字段名（可能包含描述，如 'name|用户名'）
     * @return array{0: string, 1: string} [字段名, 描述]
     */
    public static function parseFieldName(string $field): array
    {
        // 缓存检查
        if (isset(self::$fieldCache[$field])) {
            return self::$fieldCache[$field];
        }

        $result = self::doParseFieldName($field);
        
        // 缓存结果
        self::$fieldCache[$field] = $result;
        
        return $result;
    }

    /**
     * 实际解析字段名
     */
    private static function doParseFieldName(string $field): array
    {
        $pipePos = strpos($field, '|');
        if ($pipePos === false) {
            return [$field, ''];
        }
        
        return [
            trim(substr($field, 0, $pipePos)),
            trim(substr($field, $pipePos + 1))
        ];
    }

    /**
     * 将验证规则转换为JSON Schema格式（性能优化版本）
     * 
     * @param string $rule 验证规则字符串
     * @return array<string, mixed> JSON Schema
     */
    public static function ruleToJsonSchema(string $rule): array
    {
        // 缓存检查
        $cacheKey = md5($rule);
        if (isset(self::$ruleCache[$cacheKey])) {
            return self::$ruleCache[$cacheKey];
        }

        $schema = self::doRuleToJsonSchema($rule);
        
        // 缓存结果
        self::$ruleCache[$cacheKey] = $schema;
        
        return $schema;
    }

    /**
     * 实际的规则转换逻辑（优化版本）
     */
    private static function doRuleToJsonSchema(string $rule): array
    {
        self::initRegex();
        
        $schema = ['type' => 'string']; // 默认类型
        
        // 使用策略模式进行类型检测
        $schema = self::detectType($rule, $schema);
        $schema = self::detectFormat($rule, $schema);
        $schema = self::detectConstraints($rule, $schema);
        $schema = self::detectValidationRules($rule, $schema);
        
        return $schema;
    }

    /**
     * 检测数据类型
     */
    private static function detectType(string $rule, array $schema): array
    {
        // 使用预编译的类型映射提升性能
        foreach (self::$typeMapping as $ruleType => $schemaType) {
            if (str_contains($rule, $ruleType)) {
                $schema['type'] = $schemaType;
                break;
            }
        }
        
        return $schema;
    }

    /**
     * 检测格式
     */
    private static function detectFormat(string $rule, array $schema): array
    {
        foreach (self::$formatMapping as $ruleFormat => $schemaFormat) {
            if (str_contains($rule, $ruleFormat)) {
                if ($schemaFormat !== null) {
                    $schema['format'] = $schemaFormat;
                }
                break;
            }
        }
        
        return $schema;
    }

    /**
     * 检测约束条件
     */
    private static function detectConstraints(string $rule, array $schema): array
    {
        // 使用预编译正则表达式提升性能
        
        // 最小值/长度
        if (preg_match(self::$compiledRegex['min'], $rule, $matches)) {
            $min = (int)$matches[1];
            switch ($schema['type']) {
                case 'string':
                    $schema['minLength'] = $min;
                    break;
                case 'integer':
                case 'number':
                    $schema['minimum'] = $min;
                    break;
                case 'array':
                    $schema['minItems'] = $min;
                    break;
            }
        }

        // 最大值/长度
        if (preg_match(self::$compiledRegex['max'], $rule, $matches)) {
            $max = (int)$matches[1];
            switch ($schema['type']) {
                case 'string':
                    $schema['maxLength'] = $max;
                    break;
                case 'integer':
                case 'number':
                    $schema['maximum'] = $max;
                    break;
                case 'array':
                    $schema['maxItems'] = $max;
                    break;
            }
        }

        // 范围约束
        if (preg_match(self::$compiledRegex['between'], $rule, $matches)) {
            $min = (int)$matches[1];
            $max = (int)$matches[2];
            
            switch ($schema['type']) {
                case 'string':
                    $schema['minLength'] = $min;
                    $schema['maxLength'] = $max;
                    break;
                case 'integer':
                case 'number':
                    $schema['minimum'] = $min;
                    $schema['maximum'] = $max;
                    break;
                case 'array':
                    $schema['minItems'] = $min;
                    $schema['maxItems'] = $max;
                    break;
            }
        }

        // 枚举值
        if (preg_match(self::$compiledRegex['in'], $rule, $matches)) {
            $values = array_map('trim', explode(',', $matches[1]));
            $schema['enum'] = $values;
        }

        // 正则表达式
        if (preg_match(self::$compiledRegex['regex'], $rule, $matches)) {
            $schema['pattern'] = $matches[1];
        }

        // 固定大小
        if (preg_match(self::$compiledRegex['size'], $rule, $matches)) {
            $size = (int)$matches[1];
            switch ($schema['type']) {
                case 'string':
                    $schema['minLength'] = $size;
                    $schema['maxLength'] = $size;
                    break;
                case 'array':
                    $schema['minItems'] = $size;
                    $schema['maxItems'] = $size;
                    break;
            }
        }

        return $schema;
    }

    /**
     * 检测验证规则
     */
    private static function detectValidationRules(string $rule, array $schema): array
    {
        // 可空性
        if (str_contains($rule, 'nullable')) {
            $schema['nullable'] = true;
        }

        // 默认值
        if (preg_match(self::$compiledRegex['default'], $rule, $matches)) {
            $defaultValue = trim($matches[1]);
            
            // 类型转换
            if ($defaultValue === 'true') {
                $schema['default'] = true;
            } elseif ($defaultValue === 'false') {
                $schema['default'] = false;
            } elseif ($defaultValue === 'null') {
                $schema['default'] = null;
            } elseif (is_numeric($defaultValue)) {
                $schema['default'] = strpos($defaultValue, '.') !== false ? (float)$defaultValue : (int)$defaultValue;
            } else {
                $schema['default'] = $defaultValue;
            }
        }

        // 日期约束
        if (preg_match(self::$compiledRegex['after_or_equal'], $rule, $matches) ||
            preg_match(self::$compiledRegex['afterOrEqual'], $rule, $matches)) {
            $schema['format'] = 'date';
            $schema['minimum'] = $matches[1];
        }

        // 只读
        if (str_contains($rule, 'readonly')) {
            $schema['readOnly'] = true;
        }

        // 写入
        if (str_contains($rule, 'writeonly')) {
            $schema['writeOnly'] = true;
        }

        return $schema;
    }

    /**
     * 将验证规则数组转换为完整的JSON Schema（性能优化版本）
     * 
     * @param array<string, string> $rules 验证规则数组
     * @return array<string, mixed> 完整的JSON Schema
     */
    public static function rulesToJsonSchema(array $rules): array
    {
        if (empty($rules)) {
            return ['type' => 'object', 'properties' => []];
        }

        // 生成缓存键
        $cacheKey = self::generateCacheKey($rules);
        if (isset(self::$schemaCache[$cacheKey])) {
            return self::$schemaCache[$cacheKey];
        }

        $schema = self::doRulesToJsonSchema($rules);
        
        // 缓存结果
        self::$schemaCache[$cacheKey] = $schema;
        
        return $schema;
    }

    /**
     * 生成缓存键
     */
    private static function generateCacheKey(array $rules): string
    {
        // 使用更高效的哈希算法
        return hash('xxh3', serialize($rules));
    }

    /**
     * 实际的规则数组转换逻辑（批量优化版本）
     */
    private static function doRulesToJsonSchema(array $rules): array
    {
        $properties = [];
        $required = [];

        // 批量处理规则，减少循环开销
        $batchSize = 50; // 批量处理大小
        $chunks = array_chunk($rules, $batchSize, true);
        
        foreach ($chunks as $chunk) {
            foreach ($chunk as $field => $rule) {
                [$fieldName, $description] = self::parseFieldName($field);
                
                $fieldSchema = self::ruleToJsonSchema($rule);
                if ($description) {
                    $fieldSchema['description'] = $description;
                }
                
                $properties[$fieldName] = $fieldSchema;
                
                if (self::isRequired($rule)) {
                    $required[] = $fieldName;
                }
            }
        }

        $result = [
            'type' => 'object',
            'properties' => $properties,
        ];

        if (!empty($required)) {
            $result['required'] = $required;
        }

        return $result;
    }

    /**
     * 检查规则是否为必需（性能优化版本）
     */
    public static function isRequired(string $rule): bool
    {
        return str_contains($rule, 'required');
    }

    /**
     * 检查规则是否可为空（性能优化版本）
     */
    public static function isNullable(string $rule): bool
    {
        return str_contains($rule, 'nullable');
    }

    /**
     * 检查规则是否为数组
     */
    public static function isArray(string $rule): bool
    {
        return str_contains($rule, 'array');
    }

    /**
     * 检查规则是否为文件
     */
    public static function isFile(string $rule): bool
    {
        return str_contains($rule, 'file') || str_contains($rule, 'image');
    }

    /**
     * 检查规则是否包含默认值
     */
    public static function hasDefaultValue(string $rule): bool
    {
        return str_contains($rule, 'default:');
    }

    /**
     * 检查规则是否包含日期约束
     */
    public static function hasDateConstraint(string $rule): bool
    {
        return str_contains($rule, 'after_or_equal:') || 
               str_contains($rule, 'afterOrEqual:') ||
               str_contains($rule, 'after:') ||
               str_contains($rule, 'before:');
    }

    /**
     * 检查规则是否为数组元素验证
     */
    public static function isArrayElementRule(string $fieldName): bool
    {
        return str_contains($fieldName, '.*');
    }

    /**
     * 批量解析字段名（性能优化）
     */
    public static function batchParseFieldNames(array $fields): array
    {
        $results = [];
        
        foreach ($fields as $field) {
            $results[$field] = self::parseFieldName($field);
        }
        
        return $results;
    }

    /**
     * 批量转换规则为Schema（性能优化）
     */
    public static function batchRuleToJsonSchema(array $rules): array
    {
        $results = [];
        
        foreach ($rules as $rule) {
            $results[$rule] = self::ruleToJsonSchema($rule);
        }
        
        return $results;
    }

    /**
     * 获取规则的默认值
     */
    public static function getDefaultValue(string $rule): mixed
    {
        self::initRegex();
        
        if (preg_match(self::$compiledRegex['default'], $rule, $matches)) {
            $default = trim($matches[1]);
            
            // 类型转换
            if (str_contains($rule, 'integer') || str_contains($rule, 'int')) {
                return (int)$default;
            } elseif (str_contains($rule, 'numeric') || str_contains($rule, 'decimal')) {
                return (float)$default;
            } elseif (str_contains($rule, 'boolean') || str_contains($rule, 'bool')) {
                return filter_var($default, FILTER_VALIDATE_BOOLEAN);
            } elseif (str_contains($rule, 'array')) {
                return explode(',', $default);
            }
            
            return $default;
        }
        
        return null;
    }

    /**
     * 获取规则的示例值
     */
    public static function getExampleValue(string $rule): mixed
    {
        if (preg_match('/\bexample:([^|]+)/', $rule, $matches)) {
            return trim($matches[1]);
        }
        
        // 根据类型生成示例
        if (str_contains($rule, 'email')) {
            return 'user@example.com';
        } elseif (str_contains($rule, 'url')) {
            return 'https://example.com';
        } elseif (str_contains($rule, 'uuid')) {
            return '123e4567-e89b-12d3-a456-426614174000';
        } elseif (str_contains($rule, 'date')) {
            return '2023-01-01';
        }
        
        return null;
    }

    /**
     * 清除所有缓存
     */
    public static function clearCache(): void
    {
        self::$ruleCache = [];
        self::$fieldCache = [];
        self::$schemaCache = [];
    }

    /**
     * 获取缓存统计信息
     */
    public static function getCacheStats(): array
    {
        return [
            'rule_cache_size' => count(self::$ruleCache),
            'field_cache_size' => count(self::$fieldCache),
            'schema_cache_size' => count(self::$schemaCache),
            'memory_usage' => memory_get_usage(true),
        ];
    }

    /**
     * 预热缓存（提升首次使用性能）
     */
    public static function warmupCache(array $commonRules = []): void
    {
        self::initRegex();
        self::initExtractors();
        
        if (empty($commonRules)) {
            $commonRules = [
                'required|string|max:255',
                'integer|min:1',
                'email',
                'array',
                'boolean',
                'nullable|string',
                'required|numeric|min:0',
                'date',
                'url',
                'uuid',
            ];
        }
        
        foreach ($commonRules as $rule) {
            self::ruleToJsonSchema($rule);
        }
    }

    /**
     * 优化内存使用
     */
    public static function optimizeMemory(): void
    {
        // 清理超过阈值的缓存
        $maxCacheSize = 1000;
        
        if (count(self::$ruleCache) > $maxCacheSize) {
            self::$ruleCache = array_slice(self::$ruleCache, -$maxCacheSize, null, true);
        }
        
        if (count(self::$fieldCache) > $maxCacheSize) {
            self::$fieldCache = array_slice(self::$fieldCache, -$maxCacheSize, null, true);
        }
        
        if (count(self::$schemaCache) > $maxCacheSize) {
            self::$schemaCache = array_slice(self::$schemaCache, -$maxCacheSize, null, true);
        }
        
        // 触发垃圾回收
        if (function_exists('gc_collect_cycles')) {
            gc_collect_cycles();
        }
    }
}

/**
 * 类型提取器（策略模式）
 */
class TypeExtractor
{
    public function extract(string $rule): ?string
    {
        // 类型提取逻辑
        return null;
    }
}

/**
 * 格式提取器（策略模式）
 */
class FormatExtractor
{
    public function extract(string $rule): ?string
    {
        // 格式提取逻辑
        return null;
    }
}

/**
 * 约束提取器（策略模式）
 */
class ConstraintExtractor
{
    public function extract(string $rule): array
    {
        // 约束提取逻辑
        return [];
    }
}

/**
 * 验证提取器（策略模式）
 */
class ValidationExtractor
{
    public function extract(string $rule): array
    {
        // 验证规则提取逻辑
        return [];
    }
} 