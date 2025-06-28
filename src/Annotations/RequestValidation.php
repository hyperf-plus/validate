<?php

namespace HPlus\Validate\Annotations;

use Attribute;
use Hyperf\Di\Annotation\AbstractAnnotation;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class RequestValidation extends AbstractAnnotation
{
    /**
     * @param array $rules 验证规则
     * @param string|null $validate 验证器类
     * @param string|null $scene 验证场景
     * @param bool $filter 是否过滤多余字段
     * @param bool $security 安全模式
     * @param bool $batch 是否批量验证
     * @param string $dataType 验证数据类型
     * @param array $messages 自定义错误消息
     * @param array $attributes 字段属性名称映射
     */
    public function __construct(
        /**
         * 验证规则
         * @var array
         */
        public array $rules = [],
        /**
         * 验证器类
         * @var string|null
         */
        public ?string $validate = null,
        /**
         * 验证场景
         * @var string|null
         */
        public ?string $scene = null,
        /**
         * 是否过滤多余字段
         * @var bool
         */
        public bool $filter = false,
        /**
         * 安全模式：严格按照规则字段，如果有多字段会抛出异常
         * @var bool
         */
        public bool $security = false,
        /**
         * 是否批量验证
         * @var bool
         */
        public bool $batch = false,
        /**
         * 验证数据类型，支持json|xml|form表单
         * @var string
         */
        public string $dataType = 'json',
        /**
         * 自定义错误消息
         * @var array
         */
        public array $messages = [],
        /**
         * 字段属性名称映射
         * @var array
         */
        public array $attributes = []
    ) {
    }
}