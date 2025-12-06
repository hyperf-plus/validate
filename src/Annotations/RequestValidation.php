<?php

declare(strict_types=1);

namespace HPlus\Validate\Annotations;

use Attribute;
use Hyperf\Di\Annotation\AbstractAnnotation;

/**
 * 请求验证注解（统一验证入口）
 * 
 * 支持两种模式：
 * 1. 内联规则模式：直接定义 rules
 * 2. 验证器类模式：指定 validate 类和 scene 场景
 * 
 * @example 内联规则模式
 * #[RequestValidation(rules: ['name' => 'required|string'])]
 * 
 * @example 验证器类模式
 * #[RequestValidation(validate: ProductValidator::class, scene: 'create')]
 */
#[Attribute(Attribute::TARGET_METHOD)]
class RequestValidation extends AbstractAnnotation
{
    /**
     * @param array $rules 请求体验证规则 (Laravel validation 规则)
     * @param array $queryRules 查询参数验证规则
     * @param array $messages 自定义错误消息
     * @param array $attributes 字段别名
     * @param string $mode 数据获取模式：json | form | xml
     * @param bool $filter 是否过滤多余字段
     * @param bool $security 安全模式
     * @param bool $stopOnFirstFailure 是否在第一个失败时停止
     * @param string $validate 验证器类名（与 rules 二选一）
     * @param string $scene 验证场景（配合 validate 使用）
     */
    public function __construct(
        public array $rules = [],
        public array $queryRules = [],
        public array $messages = [],
        public array $attributes = [],
        public string $mode = 'json',
        public bool $filter = false,
        public bool $security = false,
        public bool $stopOnFirstFailure = false,
        public string $validate = '',
        public string $scene = ''
    ) {
    }
}