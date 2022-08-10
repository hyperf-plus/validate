<?php

namespace HPlus\Validate\Annotations;

use Attribute;
use Hyperf\Di\Annotation\AbstractAnnotation;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class Validation extends AbstractAnnotation
{
    /**
     * @param array $rules
     * @param string|null $validate
     * @param string|null $value
     * @param string|null $scene
     * @param bool $filter
     * @param bool $security
     * @param bool $batch
     * @param string $field
     */
    public function __construct(

        /**
         * 自定义规则
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
        public ?string $value = null,
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
         * 验证哪个参数
         * @var string
         */
        public string  $field = "data"


    )
    {

    }
}