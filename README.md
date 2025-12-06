# HPlus Validate 4.0

[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.1-8892BF.svg)](https://php.net)
[![Hyperf Version](https://img.shields.io/badge/hyperf-%3E%3D3.1-brightgreen.svg)](https://hyperf.io)
[![License](https://img.shields.io/badge/license-Apache--2.0-blue.svg)](LICENSE)
[![CI](https://img.shields.io/badge/CI-GitHub%20Actions-blue)](https://github.com/hyperf-plus/validate/actions)

基于 `hyperf/validation` 的请求验证组件，支持注解式验证和 FormRequest 验证器。
内置中文错误提示兜底、本地无语言包也能返回中文；默认安全过滤多余字段，可选安全模式拒绝未定义字段。

## ✨ 4.0 新特性

- 🚀 **Hyperf 原生验证**：完全基于 hyperf/validation，兼容所有 Laravel 规则
- 📝 **双模式**：内联规则 + FormRequest 验证器，支持场景
- ⚡ **性能优化**：多层缓存（配置/类检查/字段列表），更快
- 🛡️ **安全稳定**：可选安全模式拦截未定义字段，内置中文错误兜底，无语言包仍返回中文
- 🔧 **Query/Body 分离**：清晰区分 URL 参数与请求体校验
- ✅ **CI 覆盖**：GitHub Actions 多 PHP 版本自动化测试

> ⚠️ **破坏性变更**: 4.0 版本移除了 ThinkPHP 风格的 `Validate` 基类，仅支持 Hyperf 原生 `FormRequest`。

## 📦 安装

```bash
composer require hyperf-plus/validate:^4.0
```

### 依赖

```bash
composer require hyperf/validation hyperf/translation
php bin/hyperf.php vendor:publish hyperf/translation
```

## 🚀 快速开始

### 方式一：内联规则（推荐）

```php
<?php
use HPlus\Route\Annotation\PostApi;
use HPlus\Route\Annotation\ApiController;
use HPlus\Validate\Annotations\RequestValidation;

#[ApiController(prefix: '/api/users')]
class UserController
{
    #[PostApi]
    #[RequestValidation(
        rules: [
            'name' => 'required|string|max:50',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6|confirmed',
        ],
        messages: [
            'name.required' => '用户名不能为空',
            'email.unique' => '该邮箱已被注册',
        ]
    )]
    public function create()
    {
        return ['message' => 'success'];
    }
}
```

### 方式二：FormRequest 验证器

```php
// 定义验证器
use Hyperf\Validation\Request\FormRequest;

class CreateUserRequest extends FormRequest
{
    protected array $scenes = [
        'create' => ['name', 'email', 'password'],
        'update' => ['name', 'email'],
    ];

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:50',
            'email' => 'required|email',
            'password' => 'required|min:6|confirmed',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => '用户名不能为空',
        ];
    }
}

// 使用验证器
#[PostApi]
#[RequestValidation(validate: CreateUserRequest::class, scene: 'create')]
public function create() {}
```

## 📋 注解参数

```php
#[RequestValidation(
    rules: [],              // 请求体验证规则
    queryRules: [],         // URL 查询参数验证规则
    messages: [],           // 自定义错误消息
    attributes: [],         // 字段别名
    mode: 'json',           // 请求体解析模式：json | form | xml
    filter: false,          // 是否过滤多余字段
    security: false,        // 安全模式（拒绝未定义字段）
    stopOnFirstFailure: false,  // 首错即停
    validate: '',           // FormRequest 类名
    scene: '',              // 验证场景
)]
```

## 🎯 使用场景

### 验证 Query 参数（GET 请求）

```php
#[GetApi]
#[RequestValidation(
    queryRules: [
        'page' => 'integer|min:1',
        'size' => 'integer|between:1,100',
        'keyword' => 'nullable|string|max:50',
    ]
)]
public function list() {}
```

### 同时验证 Query 和 Body

```php
#[PostApi(path: '/search')]
#[RequestValidation(
    queryRules: [
        'page' => 'required|integer|min:1',
    ],
    rules: [
        'filters' => 'array',
        'sort' => 'string|in:asc,desc',
    ]
)]
public function search() {}
```

### 安全模式

拒绝请求中包含未定义的字段：

```php
#[RequestValidation(
    rules: ['name' => 'required', 'email' => 'required'],
    security: true  // 如果请求包含 name/email 以外的字段，将抛出异常
)]
```

### 过滤模式

自动过滤多余字段，只保留规则中定义的字段：

```php
#[RequestValidation(
    rules: ['name' => 'required', 'email' => 'required'],
    filter: true  // 请求体将只包含 name 和 email
)]
```

## 📐 支持的验证规则

完全兼容 Laravel/Hyperf Validation 所有规则：

| 分类 | 规则 |
|------|------|
| **基础** | `required`, `nullable`, `string`, `integer`, `numeric`, `boolean`, `array` |
| **字符串** | `email`, `url`, `ip`, `uuid`, `alpha`, `alpha_num`, `regex:pattern` |
| **数值** | `min:value`, `max:value`, `between:min,max`, `size:value`, `gt:field`, `gte:field` |
| **日期** | `date`, `date_format:format`, `before:date`, `after:date` |
| **数组** | `in:foo,bar`, `not_in:foo,bar`, `distinct`, `array` |
| **数据库** | `unique:table,column`, `exists:table,column` |
| **文件** | `file`, `image`, `mimes:jpg,png`, `max:size` |
| **关系** | `confirmed`, `same:field`, `different:field`, `required_if:field,value` |

更多规则：https://laravel.com/docs/validation#available-validation-rules

## 🛠️ 高级用法

### 自定义验证规则

```php
// config/autoload/dependencies.php
use Hyperf\Validation\Contract\ValidatorFactoryInterface;
use Hyperf\Validation\ValidatorFactory;

return [
    ValidatorFactoryInterface::class => function ($container) {
        $factory = $container->get(ValidatorFactory::class);
        
        $factory->extend('phone', function ($attribute, $value) {
            return preg_match('/^1[3-9]\d{9}$/', $value);
        });
        
        return $factory;
    },
];

// 使用
#[RequestValidation(rules: ['mobile' => 'required|phone'])]
```

### 嵌套数组验证

```php
#[RequestValidation(
    rules: [
        'users' => 'required|array|min:1',
        'users.*.name' => 'required|string',
        'users.*.email' => 'required|email',
    ]
)]
```

### 条件验证

```php
#[RequestValidation(
    rules: [
        'type' => 'required|in:person,company',
        'id_card' => 'required_if:type,person|size:18',
        'license' => 'required_if:type,company',
    ]
)]
```

## ❌ 错误处理

验证失败抛出 `ValidateException`（HTTP 422）：

```php
// app/Exception/Handler/ValidationExceptionHandler.php
use HPlus\Validate\Exception\ValidateException;
use Hyperf\ExceptionHandler\ExceptionHandler;

class ValidationExceptionHandler extends ExceptionHandler
{
    public function handle(Throwable $throwable, ResponseInterface $response)
    {
        if ($throwable instanceof ValidateException) {
            return $response->withStatus(422)->json([
                'code' => 422,
                'message' => $throwable->getMessage(),
            ]);
        }
        return $response;
    }

    public function isValid(Throwable $throwable): bool
    {
        return $throwable instanceof ValidateException;
    }
}
```

## 🧪 测试覆盖

```
tests/
├── Unit/
│   ├── RuleParserTest.php          # 规则解析器测试
│   └── ValidationAspectTest.php    # 验证切面测试
├── Feature/
│   ├── ValidationRulesTest.php     # 验证规则功能测试
│   ├── ValidationModeTest.php      # 验证模式测试
│   └── ValidationAspectFullCoverageTest.php  # 完整覆盖测试
└── Performance/
    └── ValidationPerformanceTest.php  # 性能测试
```

运行测试：

```bash
composer test
```

## 📊 与 3.x 版本对比

| 特性 | 3.x | 4.0 |
|------|-----|-----|
| ThinkPHP Validate | ✅ | ❌ 移除 |
| Hyperf FormRequest | ✅ | ✅ |
| 内联规则 | ✅ | ✅ |
| `dateType` 参数 | ✅ | ❌ 改为 `mode` |
| `validate` + `scene` | 分离注解 | 统一到 `RequestValidation` |
| 缓存统计 | ✅ | ❌ 移除（无意义开销） |

### 迁移指南

```php
// 3.x (旧)
#[Validation(validate: UserValidator::class, scene: 'create')]
#[RequestValidation(rules: [...], dateType: 'json')]

// 4.0 (新)
#[RequestValidation(validate: UserValidator::class, scene: 'create')]
#[RequestValidation(rules: [...], mode: 'json')]
```

## 📄 License

MIT