<?php

namespace HPlus\Validate\Annotations;

use Attribute;
use Hyperf\Di\Annotation\AbstractAnnotation;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class RequestValidation extends AbstractAnnotation
{
    /**
     * @param array $rules
     * @param string|null $validate
     * @param string|null $scene
     * @param bool $filter
     * @param bool $security
     * @param bool $batch
     * @param string $dateType
     */
    public function __construct(
        /**
         * 规则类
         * @var string
         */
        public array   $rules = [],
        /**
         * 验证器
         * @var string
         */
        public ?string $validate = null,
        /**
         * 场景
         * @var string
         */
        public ?string $scene = null,
        /**
         * 是否过滤多余字段
         * @var bool
         */
        public bool    $filter = false,
        /**
         * 安全模式严格按照规则字段，如果多字段会抛出异常
         * @var bool
         */
        public bool    $security = false,
        /**
         * 是否批量验证
         * @var bool
         */
        public bool    $batch = false,
        /**
         * 验证数据类型，支持json|xml|form表单
         * @var string
         */
        public string  $dateType = 'json'
    )
    {
    }
}