<?php

declare(strict_types=1);

namespace HPlus\Validate\Annotations;

use Attribute;
use Hyperf\Di\Annotation\AbstractAnnotation;

/**
 * 请求验证注解
 * 基于 hyperf/validation 的路由验证适配器
 */
#[Attribute(Attribute::TARGET_METHOD)]
class RequestValidation extends AbstractAnnotation
{
    /**
     * @param array $rules 验证规则 (Laravel validation 规则)
     * @param array $messages 自定义错误消息
     * @param array $attributes 字段别名（用于错误消息）
     * @param string $mode 验证模式：json(请求体) | query(查询参数) | all(合并验证)
     * @param bool $filter 是否过滤多余字段（只保留规则中定义的字段）
     * @param bool $security 安全模式（请求中有未定义字段时抛出异常）
     * @param bool $stopOnFirstFailure 是否在第一个失败时停止
     */
    public function __construct(
        public array $rules = [],
        public array $messages = [],
        public array $attributes = [],
        public string $mode = 'json',
        public bool $filter = false,
        public bool $security = false,
        public bool $stopOnFirstFailure = false
    ) {
    }
}