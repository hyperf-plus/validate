<?php

declare(strict_types=1);

namespace Examples;

use HPlus\Route\Annotation\GetApi;
use HPlus\Route\Annotation\PostApi;
use HPlus\Route\Annotation\PutApi;
use HPlus\Route\Annotation\DeleteApi;
use HPlus\Route\Annotation\ApiController;
use HPlus\Validate\Annotations\RequestValidation;

/**
 * 用户控制器示例
 */
#[ApiController(prefix: '/api/users')]
class UserController
{
    /**
     * 获取用户列表（Query 参数验证）
     */
    #[GetApi(path: '')]
    #[RequestValidation(
        rules: [
            'page' => 'required|integer|min:1',
            'size' => 'required|integer|between:1,100',
            'keyword' => 'nullable|string|max:50',
            'status' => 'nullable|in:active,inactive,banned',
            'sort' => 'nullable|in:created_at,updated_at,name',
            'order' => 'nullable|in:asc,desc',
        ],
        messages: [
            'page.required' => '页码不能为空',
            'size.between' => '每页数量必须在1-100之间',
        ],
        attributes: [
            'page' => '页码',
            'size' => '每页数量',
            'keyword' => '关键词',
            'status' => '状态',
        ],
        mode: 'query'
    )]
    public function index()
    {
        return [
            'code' => 0,
            'message' => 'success',
            'data' => [
                // 用户列表
            ]
        ];
    }

    /**
     * 获取用户详情
     */
    #[GetApi(path: '/{id}')]
    public function show(int $id)
    {
        return [
            'code' => 0,
            'message' => 'success',
            'data' => [
                'id' => $id,
                // 用户详情
            ]
        ];
    }

    /**
     * 创建用户（JSON Body 验证）
     */
    #[PostApi(path: '')]
    #[RequestValidation(
        rules: [
            'username' => 'required|string|between:3,20|alpha_dash|unique:users,username',
            'email' => 'required|email|max:100|unique:users,email',
            'password' => 'required|string|min:6|max:32|confirmed',
            'mobile' => 'required|regex:/^1[3-9]\d{9}$/',
            'nickname' => 'nullable|string|max:50',
            'avatar' => 'nullable|url',
            'age' => 'nullable|integer|between:1,150',
            'gender' => 'nullable|in:male,female,other',
            'bio' => 'nullable|string|max:500',
            'tags' => 'nullable|array|max:10',
            'tags.*' => 'string|max:20',
            'settings' => 'nullable|array',
            'settings.email_notify' => 'nullable|boolean',
            'settings.sms_notify' => 'nullable|boolean',
        ],
        messages: [
            'username.required' => '用户名不能为空',
            'username.unique' => '该用户名已被使用',
            'username.alpha_dash' => '用户名只能包含字母、数字、破折号和下划线',
            'email.unique' => '该邮箱已被注册',
            'password.confirmed' => '两次密码输入不一致',
            'mobile.regex' => '手机号格式不正确',
        ],
        attributes: [
            'username' => '用户名',
            'email' => '邮箱',
            'password' => '密码',
            'mobile' => '手机号',
            'nickname' => '昵称',
            'avatar' => '头像',
            'age' => '年龄',
            'gender' => '性别',
            'bio' => '简介',
            'tags' => '标签',
            'settings.email_notify' => '邮件通知',
            'settings.sms_notify' => '短信通知',
        ],
        mode: 'json'
    )]
    public function create()
    {
        return [
            'code' => 0,
            'message' => '创建成功',
            'data' => [
                'id' => 1,
            ]
        ];
    }

    /**
     * 更新用户
     */
    #[PutApi(path: '/{id}')]
    #[RequestValidation(
        rules: [
            'username' => 'sometimes|string|between:3,20|alpha_dash',
            'email' => 'sometimes|email|max:100',
            'nickname' => 'nullable|string|max:50',
            'avatar' => 'nullable|url',
            'age' => 'nullable|integer|between:1,150',
            'gender' => 'nullable|in:male,female,other',
            'bio' => 'nullable|string|max:500',
        ],
        messages: [
            'username.alpha_dash' => '用户名只能包含字母、数字、破折号和下划线',
        ],
        mode: 'json',
        stopOnFirstFailure: false
    )]
    public function update(int $id)
    {
        return [
            'code' => 0,
            'message' => '更新成功',
        ];
    }

    /**
     * 删除用户
     */
    #[DeleteApi(path: '/{id}')]
    public function delete(int $id)
    {
        return [
            'code' => 0,
            'message' => '删除成功',
        ];
    }

    /**
     * 批量删除（All 模式：合并 Query 和 Body）
     */
    #[PostApi(path: '/batch-delete')]
    #[RequestValidation(
        rules: [
            'reason' => 'required|string|max:200',  // 来自 query
            'ids' => 'required|array|min:1|max:100',  // 来自 body
            'ids.*' => 'integer|min:1',
        ],
        messages: [
            'ids.required' => '请选择要删除的用户',
            'ids.max' => '一次最多删除100个用户',
        ],
        mode: 'all'
    )]
    public function batchDelete()
    {
        return [
            'code' => 0,
            'message' => '批量删除成功',
        ];
    }

    /**
     * 修改密码
     */
    #[PostApi(path: '/change-password')]
    #[RequestValidation(
        rules: [
            'old_password' => 'required|string',
            'new_password' => 'required|string|min:6|max:32|different:old_password|confirmed',
        ],
        messages: [
            'old_password.required' => '请输入原密码',
            'new_password.different' => '新密码不能与原密码相同',
            'new_password.confirmed' => '两次密码输入不一致',
        ],
        attributes: [
            'old_password' => '原密码',
            'new_password' => '新密码',
        ],
        mode: 'json',
        stopOnFirstFailure: true
    )]
    public function changePassword()
    {
        return [
            'code' => 0,
            'message' => '密码修改成功',
        ];
    }

    /**
     * 上传头像
     */
    #[PostApi(path: '/upload-avatar')]
    #[RequestValidation(
        rules: [
            'avatar' => 'required|file|image|max:2048|mimes:jpg,jpeg,png,gif',
        ],
        messages: [
            'avatar.required' => '请选择头像文件',
            'avatar.image' => '头像必须是图片',
            'avatar.max' => '头像大小不能超过2MB',
            'avatar.mimes' => '头像格式必须是jpg、jpeg、png或gif',
        ],
        mode: 'json'
    )]
    public function uploadAvatar()
    {
        return [
            'code' => 0,
            'message' => '上传成功',
            'data' => [
                'url' => 'https://example.com/avatar.jpg',
            ]
        ];
    }

    /**
     * 高级搜索（复杂条件验证）
     */
    #[PostApi(path: '/search')]
    #[RequestValidation(
        rules: [
            'filters' => 'required|array',
            'filters.*.field' => 'required|string|in:username,email,mobile,status,created_at',
            'filters.*.operator' => 'required|string|in:eq,ne,gt,gte,lt,lte,like,in',
            'filters.*.value' => 'required',
            'sort' => 'nullable|array',
            'sort.*.field' => 'required|string',
            'sort.*.order' => 'required|in:asc,desc',
            'page' => 'required|integer|min:1',
            'size' => 'required|integer|between:1,100',
        ],
        mode: 'json'
    )]
    public function search()
    {
        return [
            'code' => 0,
            'message' => 'success',
            'data' => [
                // 搜索结果
            ]
        ];
    }
}
