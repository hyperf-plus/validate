<?php

declare(strict_types=1);

namespace HPlus\Validate\Tests\Stub;

use HPlus\Validate\Annotations\RequestValidation;

/**
 * Mock 控制器用于测试
 */
class MockController
{
    /**
     * 基础验证测试
     */
    #[RequestValidation(
        rules: [
            'name' => 'required|string|max:50',
            'email' => 'required|email',
            'age' => 'nullable|integer|between:1,150',
        ]
    )]
    public function basicValidation(): array
    {
        return ['success' => true];
    }

    /**
     * Query 模式测试
     */
    #[RequestValidation(
        rules: [
            'page' => 'required|integer|min:1',
            'size' => 'required|integer|between:1,100',
        ],
        mode: 'query'
    )]
    public function queryValidation(): array
    {
        return ['success' => true];
    }

    /**
     * 自定义消息测试
     */
    #[RequestValidation(
        rules: [
            'email' => 'required|email',
        ],
        messages: [
            'email.required' => '邮箱不能为空',
            'email.email' => '邮箱格式不正确',
        ]
    )]
    public function customMessages(): array
    {
        return ['success' => true];
    }

    /**
     * 自定义属性测试
     */
    #[RequestValidation(
        rules: [
            'user_email' => 'required|email',
        ],
        attributes: [
            'user_email' => '用户邮箱',
        ]
    )]
    public function customAttributes(): array
    {
        return ['success' => true];
    }

    /**
     * 停止首个失败测试
     */
    #[RequestValidation(
        rules: [
            'field1' => 'required',
            'field2' => 'required',
            'field3' => 'required',
        ],
        stopOnFirstFailure: true
    )]
    public function stopOnFirstFailure(): array
    {
        return ['success' => true];
    }

    /**
     * All 模式测试
     */
    #[RequestValidation(
        rules: [
            'query_param' => 'required|string',
            'body_param' => 'required|string',
        ],
        mode: 'all'
    )]
    public function allMode(): array
    {
        return ['success' => true];
    }

    /**
     * 复杂验证测试
     */
    #[RequestValidation(
        rules: [
            'users' => 'required|array',
            'users.*.name' => 'required|string|max:50',
            'users.*.email' => 'required|email',
            'users.*.age' => 'nullable|integer|between:18,100',
        ]
    )]
    public function complexValidation(): array
    {
        return ['success' => true];
    }

    /**
     * 无验证注解
     */
    public function noValidation(): array
    {
        return ['success' => true];
    }
}
