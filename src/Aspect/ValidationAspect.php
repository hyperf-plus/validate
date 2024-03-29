<?php

declare (strict_types=1);
namespace HPlus\Validate\Aspect;

use HPlus\Validate\Annotations\RequestValidation;
use HPlus\Validate\Annotations\Validation;
use HPlus\Validate\Validate;
use HPlus\Validate\Exception\ValidateException;
use Hyperf\Di\Annotation\Aspect;
use Hyperf\Di\Aop\AbstractAspect;
use Hyperf\Di\Aop\ProceedingJoinPoint;
use Hyperf\Di\Exception\Exception;
use Hyperf\Context\Context;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;

#[Aspect]
class ValidationAspect extends AbstractAspect
{
    
    protected $container;
    
    protected $request;
    // 要切入的类，可以多个，亦可通过 :: 标识到具体的某个方法，通过 * 可以模糊匹配
    public array $annotations = [Validation::class, RequestValidation::class];
    
    public function __construct(ContainerInterface $container, ServerRequestInterface $Request)
    {
        $this->container = $container;
        $this->request = $this->container->get(ServerRequestInterface::class);
    }
    /**
     * @param ProceedingJoinPoint $proceedingJoinPoint
     * @return mixed
     * @throws Exception
     * @throws ValidateException
     */
    public function process(ProceedingJoinPoint $proceedingJoinPoint)
    {
        foreach ($proceedingJoinPoint->getAnnotationMetadata()->method as $validation) {
            /**
             * @var Validation $validation
             */
            switch (true) {
                case $validation instanceof RequestValidation:
                    $verData = $this->request->all();
                    $this->validationData($validation, $verData, $validation->validate, $proceedingJoinPoint, true);
                    break;
                case $validation instanceof Validation:
                    $verData = $proceedingJoinPoint->arguments['keys'][$validation->field];
                    $this->validationData($validation, $verData, $validation->validate, $proceedingJoinPoint);
                    break;
                default:
                    break;
            }
        }
        return $proceedingJoinPoint->process();
    }
    /**
     * @param $validation
     * @param $verData
     * @param $class
     * @param $proceedingJoinPoint
     * @param $isRequest
     * @throws ValidateException
     */
    private function validationData($validation, $verData, $class, $proceedingJoinPoint, $isRequest = false)
    {
        /**
         * @var Validation $validation
         */
        /**
         * @var Validate $validate
         */
        if ($validation->rules != null) {
            $validate = new Validate();
            $rules = $validation->rules;
        } else {
            if (class_exists($class)) {
                $validate = new $class();
            } else {
                throw new ValidateException('class not exists:' . $class);
            }
            if ($validation->scene == '') {
                $validation->scene = $proceedingJoinPoint->methodName;
            }
            $rules = $validate->getSceneRule($validation->scene);
        }
        if ($validate->batch($validation->batch)->check($verData, $rules, $validation->scene) === false) {
            throw new ValidateException((string)$validate->getError());
        }
        if ($validation->security) {
            $fields = $this->getFields($rules);
            foreach ($verData as $key => $item) {
                if (!in_array($key, $fields)) {
                    throw new ValidateException('params ' . $key . ' invalid');
                }
            }
        }
        if ($validation->filter) {
            $fields = $this->getFields($rules);
            $verData = array_filter($verData, function ($value, $key) use($fields) {
                return in_array($key, $fields);
            }, ARRAY_FILTER_USE_BOTH);
            switch ($isRequest) {
                case true:
                    Context::override(ServerRequestInterface::class, function (ServerRequestInterface $request) use($verData) {
                        return $request->withParsedBody($verData);
                    });
                    break;
                default:
                    $proceedingJoinPoint->arguments['keys'][$validation->field] = $verData;
                    break;
            }
        }
    }
    
    protected function getFields(array $rules)
    {
        $fields = [];
        foreach ($rules as $field => $rule) {
            if (is_numeric($field)) {
                $field = $rule;
            }
            if (strpos($field, '|')) {
                // 字段|描述 用于指定属性名称
                list($field, ) = explode('|', $field);
            }
            $fields[] = $field;
        }
        return $fields;
    }
}