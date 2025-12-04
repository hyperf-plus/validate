<?php

declare(strict_types=1);

/**
 * Hyperf Validation 配置
 */
return [
    /*
    |--------------------------------------------------------------------------
    | 默认验证语言
    |--------------------------------------------------------------------------
    */
    'default' => 'zh_CN',

    /*
    |--------------------------------------------------------------------------
    | 验证错误消息
    |--------------------------------------------------------------------------
    */
    'messages' => [
        'required' => ':attribute不能为空',
        'required_if' => '当:other为:value时，:attribute不能为空',
        'required_with' => '当:values存在时，:attribute不能为空',
        'required_without' => '当:values不存在时，:attribute不能为空',
        'string' => ':attribute必须是字符串',
        'integer' => ':attribute必须是整数',
        'numeric' => ':attribute必须是数字',
        'boolean' => ':attribute必须是布尔值',
        'array' => ':attribute必须是数组',
        'email' => ':attribute格式不正确',
        'url' => ':attribute必须是有效的URL',
        'ip' => ':attribute必须是有效的IP地址',
        'date' => ':attribute必须是有效的日期',
        'date_format' => ':attribute格式必须为:format',
        'min' => [
            'numeric' => ':attribute不能小于:min',
            'string' => ':attribute长度不能少于:min个字符',
            'array' => ':attribute不能少于:min项',
        ],
        'max' => [
            'numeric' => ':attribute不能大于:max',
            'string' => ':attribute长度不能超过:max个字符',
            'array' => ':attribute不能超过:max项',
        ],
        'between' => [
            'numeric' => ':attribute必须在:min到:max之间',
            'string' => ':attribute长度必须在:min到:max个字符之间',
            'array' => ':attribute必须在:min到:max项之间',
        ],
        'in' => ':attribute必须在:values中',
        'not_in' => ':attribute不能在:values中',
        'unique' => ':attribute已存在',
        'exists' => ':attribute不存在',
        'regex' => ':attribute格式不正确',
        'confirmed' => ':attribute两次输入不一致',
        'different' => ':attribute和:other必须不同',
        'same' => ':attribute和:other必须相同',
        'size' => [
            'numeric' => ':attribute必须等于:size',
            'string' => ':attribute长度必须为:size个字符',
            'array' => ':attribute必须包含:size项',
        ],
        'alpha' => ':attribute只能包含字母',
        'alpha_dash' => ':attribute只能包含字母、数字、破折号和下划线',
        'alpha_num' => ':attribute只能包含字母和数字',
        'json' => ':attribute必须是有效的JSON字符串',
        'file' => ':attribute必须是文件',
        'image' => ':attribute必须是图片',
        'mimes' => ':attribute文件类型必须是:values',
        'mimetypes' => ':attribute文件MIME类型必须是:values',
        'uploaded' => ':attribute上传失败',
        'max_filesize' => ':attribute文件大小不能超过:max KB',
    ],

    /*
    |--------------------------------------------------------------------------
    | 自定义属性名称
    |--------------------------------------------------------------------------
    */
    'attributes' => [
        'name' => '名称',
        'username' => '用户名',
        'email' => '邮箱',
        'password' => '密码',
        'password_confirmation' => '确认密码',
        'mobile' => '手机号',
        'phone' => '电话号码',
        'title' => '标题',
        'content' => '内容',
        'description' => '描述',
        'status' => '状态',
        'type' => '类型',
        'category' => '分类',
        'image' => '图片',
        'file' => '文件',
        'url' => '链接',
        'address' => '地址',
        'city' => '城市',
        'province' => '省份',
        'country' => '国家',
        'code' => '编码',
        'sort' => '排序',
        'remark' => '备注',
    ],
];
