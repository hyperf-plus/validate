<?php

namespace HPlus\Validate\Annotations;

use Hyperf\Di\Annotation\AbstractAnnotation;

/**
 * @Annotation
 * @Target("METHOD")
 */
class RequestValidation extends AbstractAnnotation
{
    /**
     * 规则类
     * @var string
     */
    public $rules = [];
    /**
     * 验证器
     * @var string
     */
    public $validate = '';
    /**
     * 场景
     * @var string
     */
    public $scene = '';
    /**
     * 场景
     * @var string
     */
    public $value = '';
    /**
     * 是否过滤多余字段
     * @var bool
     */
    public $filter = false;
    /**
     * 安全模式严格按照规则字段，如果多字段会抛出异常
     * @var bool
     */
    public $security = false;
    /**
     * 是否批量验证
     * @var bool
     */
    public $batch = false;
    /**
     * 验证数据类型，支持json|xml|form表单
     * @var string
     */
    public $dateType = 'json';

    public function __construct($value = null)
    {
        parent::__construct($value);
        $this->bindMainProperty('scene', $value);
    }
}