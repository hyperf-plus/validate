# HPlus Validate - 智能请求验证组件

[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.0-8892BF.svg)](https://php.net)
[![Hyperf Version](https://img.shields.io/badge/hyperf-%3E%3D3.0-brightgreen.svg)](https://hyperf.io)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

一个为 Hyperf 框架打造的智能请求验证组件，支持注解驱动、自动类型转换、友好错误提示等特性。

## ✨ 核心特性

- 🎯 **注解驱动验证** - 使用注解定义验证规则，简洁直观
- 🔄 **自动类型转换** - 智能转换请求参数类型
- 📝 **友好错误提示** - 支持自定义错误消息和多语言
- 🚀 **高性能设计** - 规则缓存、懒加载优化
- 🔧 **灵活扩展** - 支持自定义验证规则
- 🤝 **无缝集成** - 与 Route 和 Swagger 组件完美配合

## 📦 安装

```bash
composer require hyperf-plus/validate
```

### ✅ 兼容性说明

**本包支持无缝升级**，完全向后兼容。所有公共API和注解保持不变：
- `RequestValidation` 注解的所有参数保持兼容
- `RuleParser` 的公共方法签名未改变
- 仅进行了内部性能优化，不影响外部使用

## 🚀 快速开始

### 1. 基础使用

```php
<?php

use HPlus\Validate\Annotations\RequestValidation;
use HPlus\Route\Annotation\PostApi;

class UserController
{
    #[PostApi]
    #[RequestValidation(
        rules: [
            'username' => 'required|string|min:3|max:20',
            'email' => 'required|email',
            'age' => 'integer|min:18|max:100',
            'password' => 'required|string|min:6'
        ]
    )]
    public function create()
    {
        // 验证通过后执行
        $data = $this->request->getParsedBody();
        // ...
    }
}
```

### 2. 字段描述

```php
#[RequestValidation(
    rules: [
        'username|用户名' => 'required|string|min:3|max:20',
        'email|邮箱地址' => 'required|email',
        'age|年龄' => 'integer|min:18|max:100',
        'password|密码' => 'required|string|min:6'
    ]
)]
```

### 3. 自定义错误消息

```php
#[RequestValidation(
    rules: [
        'username' => 'required|string|min:3|max:20',
        'email' => 'required|email'
    ],
    messages: [
        'username.required' => '请输入用户名',
        'username.min' => '用户名至少需要3个字符',
        'email.required' => '请输入邮箱地址',
        'email.email' => '邮箱格式不正确'
    ]
)]
```

### 4. 嵌套验证

```php
#[RequestValidation(
    rules: [
        'user' => 'required|array',
        'user.name' => 'required|string',
        'user.email' => 'required|email',
        'user.profile' => 'array',
        'user.profile.bio' => 'string|max:200',
        'tags' => 'array',
        'tags.*' => 'string|distinct'
    ]
)]
```

## 📋 验证规则

### 基础规则

| 规则 | 说明 | 示例 |
|------|------|------|
| required | 必填 | `required` |
| nullable | 可为 null | `nullable` |
| string | 字符串 | `string` |
| integer | 整数 | `integer` |
| numeric | 数字 | `numeric` |
| boolean | 布尔值 | `boolean` |
| array | 数组 | `array` |
| json | JSON 字符串 | `json` |

### 字符串规则

| 规则 | 说明 | 示例 |
|------|------|------|
| min:n | 最小长度 | `min:3` |
| max:n | 最大长度 | `max:20` |
| length:n | 固定长度 | `length:11` |
| email | 邮箱格式 | `email` |
| url | URL 格式 | `url` |
| ip | IP 地址 | `ip` |
| alpha | 纯字母 | `alpha` |
| alpha_num | 字母数字 | `alpha_num` |
| alpha_dash | 字母数字下划线横线 | `alpha_dash` |
| regex:pattern | 正则匹配 | `regex:/^1[3-9]\d{9}$/` |

### 数字规则

| 规则 | 说明 | 示例 |
|------|------|------|
| min:n | 最小值 | `min:0` |
| max:n | 最大值 | `max:100` |
| between:min,max | 范围 | `between:1,100` |
| gt:n | 大于 | `gt:0` |
| gte:n | 大于等于 | `gte:0` |
| lt:n | 小于 | `lt:100` |
| lte:n | 小于等于 | `lte:100` |

### 数组规则

| 规则 | 说明 | 示例 |
|------|------|------|
| min:n | 最少元素 | `min:1` |
| max:n | 最多元素 | `max:10` |
| size:n | 固定数量 | `size:3` |
| distinct | 元素唯一 | `distinct` |

### 特殊规则

| 规则 | 说明 | 示例 |
|------|------|------|
| in:list | 枚举值 | `in:active,inactive,pending` |
| not_in:list | 排除值 | `not_in:deleted,banned` |
| confirmed | 确认字段 | `confirmed` |
| different:field | 不同于字段 | `different:username` |
| same:field | 相同于字段 | `same:password` |
| date | 日期格式 | `date` |
| date_format:format | 日期格式 | `date_format:Y-m-d` |
| before:date | 早于日期 | `before:2024-12-31` |
| after:date | 晚于日期 | `after:2024-01-01` |
| file | 文件 | `file` |
| image | 图片 | `image` |
| mimes:list | 文件类型 | `mimes:jpg,png,pdf` |

## 🎯 高级用法

### 1. 条件验证

```php
#[RequestValidation(
    rules: [
        'type' => 'required|in:personal,company',
        'name' => 'required|string',
        'company_name' => 'required_if:type,company|string',
        'tax_number' => 'required_if:type,company|string'
    ]
)]
```

### 2. 自定义验证规则

```php
use HPlus\Validate\ValidateRule;

// 注册自定义规则
ValidateRule::extend('phone', function ($attribute, $value, $parameters) {
    return preg_match('/^1[3-9]\d{9}$/', $value);
});

// 使用自定义规则
#[RequestValidation(
    rules: [
        'mobile' => 'required|phone'
    ],
    messages: [
        'mobile.phone' => '手机号格式不正确'
    ]
)]
```

### 3. 验证场景

```php
#[RequestValidation(
    rules: [
        'username' => 'required|string|min:3',
        'email' => 'required|email',
        'password' => 'required|string|min:6'
    ],
    scene: 'create'  // 创建场景
)]
public function create() {}

#[RequestValidation(
    rules: [
        'username' => 'string|min:3',
        'email' => 'email',
        'password' => 'string|min:6'
    ],
    scene: 'update'  // 更新场景（字段可选）
)]
public function update() {}
```

### 4. 数据类型

```php
#[RequestValidation(
    rules: [
        'name' => 'required|string',
        'tags' => 'array',
        'settings' => 'json'
    ],
    dateType: 'json'  // 请求体类型：json(默认)、form、query
)]
```

### 5. 前置处理

```php
#[RequestValidation(
    rules: [
        'email' => 'required|email',
        'username' => 'required|string'
    ],
    before: function (&$data) {
        // 前置处理：转换小写
        $data['email'] = strtolower($data['email'] ?? '');
        $data['username'] = trim($data['username'] ?? '');
    }
)]
```

## 🔧 验证器类

对于复杂验证逻辑，建议使用独立的验证器类：

```php
<?php

namespace App\Validator;

use HPlus\Validate\Validate;

class UserValidator extends Validate
{
    protected array $rule = [
        'username' => 'required|string|min:3|max:20',
        'email' => 'required|email',
        'password' => 'required|string|min:6',
        'age' => 'integer|between:18,100'
    ];
    
    protected array $message = [
        'username.required' => '请输入用户名',
        'email.email' => '邮箱格式不正确',
        'password.min' => '密码至少6个字符'
    ];
    
    protected array $scene = [
        'create' => ['username', 'email', 'password'],
        'update' => ['username', 'email'],
        'login' => ['email', 'password']
    ];
}

// 使用验证器
#[PostApi]
#[RequestValidation(validator: UserValidator::class, scene: 'create')]
public function create() {}
```

## 🤝 与其他组件集成

### 与 Route 组件集成

验证组件会自动识别路由参数：

```php
#[GetApi]
#[RequestValidation(rules: [
    'page' => 'integer|min:1|default:1',
    'size' => 'integer|min:1|max:100|default:20',
    'keyword' => 'string|max:50'
])]
public function index() {}
```

### 与 Swagger 组件集成

验证规则会自动转换为 OpenAPI 参数定义：

- `required` → required: true
- `integer` → type: integer
- `min/max` → minimum/maximum
- `enum/in` → enum 数组
- 字段描述 → description

## ⚡ 性能优化

- **规则缓存** - 编译后的规则缓存复用
- **懒加载** - 按需加载验证器
- **批量验证** - 一次验证所有规则
- **智能短路** - 失败即停止后续验证

## 🛠️ 配置

在 `config/autoload/validate.php` 中配置：

```php
return [
    // 默认错误码
    'error_code' => 422,
    
    // 默认错误消息
    'error_message' => '验证失败',
    
    // 是否返回所有错误
    'return_all_errors' => true,
    
    // 自定义错误格式
    'error_format' => function ($errors) {
        return [
            'code' => 422,
            'message' => '验证失败',
            'errors' => $errors
        ];
    }
];
```

## 📝 最佳实践

1. **规则组织**
   - 简单验证用注解
   - 复杂验证用验证器类
   - 共用规则抽取为基类

2. **错误处理**
   - 提供友好的错误提示
   - 使用字段描述而非字段名
   - 支持多语言错误消息

3. **性能考虑**
   - 合理使用验证场景
   - 避免过度复杂的正则
   - 大数据量考虑分批验证

## 🐛 常见问题

1. **验证不生效**
   - 检查注解是否正确导入
   - 确认中间件是否注册
   - 验证规则语法是否正确

2. **类型转换失败**
   - 检查数据类型是否匹配
   - 使用 `nullable` 处理可选字段
   - 注意 `dateType` 设置

3. **自定义规则不工作**
   - 确认规则已注册
   - 检查规则名称是否冲突
   - 验证闭包返回值

## 📄 许可证

MIT License

## 🤝 贡献

欢迎提交 Issue 和 Pull Request！