<?php

namespace HPlus\Validate\Annotations;

use Attribute;
use Hyperf\Di\Annotation\AbstractAnnotation;

/**
 * @Annotation
 * @Target({"CLASS", "METHOD"})
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class Scene extends AbstractAnnotation
{
    /**
     * @param string $name 场景名称
     * @param array $fields 场景字段
     * @param array $rules 场景规则
     * @param bool $only 是否只验证指定字段
     */
    public function __construct(
        /**
         * 场景名称
         * @var string
         */
        public string $name = '',
        /**
         * 场景字段
         * @var array
         */
        public array  $fields = [],
        /**
         * 场景规则
         * @var array
         */
        public array  $rules = [],
        /**
         * 是否只验证指定字段
         * @var bool
         */
        public bool   $only = false
    )
    {
    }
}