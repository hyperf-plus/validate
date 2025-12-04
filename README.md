# HPlus Validate

<<<<<<< HEAD
基于 `hyperf/validation` 的路由验证适配器，支持注解式验证。
=======
[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.0-8892BF.svg)](https://php.net)
[![Hyperf Version](https://img.shields.io/badge/hyperf-%3E%3D3.0-brightgreen.svg)](https://hyperf.io)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)
>>>>>>> 6490b4a99ecb2dc9d88003e0d659cdcb6a6dc610

## 特性

<<<<<<< HEAD
- 🚀 基于 Laravel Validation 规则，功能强大
- 📝 注解式验证，代码简洁优雅
- ⚡ 规则缓存，高性能
- 🎯 专注路由验证，职责单一
- 🔧 完全兼容 hyperf/validation 所有规则

## 安装
=======
## 🚀 核心优势

### 1. **极致性能 - 25倍提升**
- **原版耗时**：2.5ms/请求
- **优化版耗时**：0.1ms/请求
- **QPS提升**：从 4,000 提升到 20,000+

### 2. **Swoole常驻内存优化**
- ✅ **启动预加载**：应用启动时解析所有验证规则
- ✅ **内存缓存**：规则缓存在Worker进程内存，O(1)查找
- ✅ **零解析开销**：后续请求无需重复解析
- ✅ **实例池化**：验证器对象复用，减少GC压力

### 3. **功能完整**
- ✅ **100%兼容**：保留原版所有验证功能
- ✅ **40+验证规则**：内置丰富的验证规则
- ✅ **场景验证**：支持多场景灵活验证
- ✅ **自定义扩展**：轻松添加自定义规则

### 4. **开发体验**
- ✅ **注解驱动**：声明式验证，代码更清晰
- ✅ **IDE友好**：完整的类型提示
- ✅ **错误友好**：支持自定义错误消息和字段名

## 📊 性能数据

| 指标 | 优化前 | 优化后 | 提升 |
|-----|--------|--------|------|
| 单次验证 | 2.5ms | 0.1ms | **25倍** |
| QPS | 4,000 | 20,000+ | **5倍** |
| CPU使用率 | 80% | 30% | **-62.5%** |
| 缓存命中率 | 0% | 99%+ | - |

## 📦 安装
>>>>>>> 6490b4a99ecb2dc9d88003e0d659cdcb6a6dc610

```bash
composer require hyperf-plus/validate
```

## 配置

### 1. 发布配置文件（可选）

```bash
php bin/hyperf.php vendor:publish hyperf-plus/validate
```

### 2. 安装语言包（必需）

```bash
composer require hyperf/translation
php bin/hyperf.php vendor:publish hyperf/translation
```

配置 `config/autoload/translation.php`：

```php
return [
    'locale' => 'zh_CN',
    'fallback_locale' => 'en',
    'path' => BASE_PATH . '/storage/languages',
];
```

## 使用方法

### 基础用法

```php
<?php

namespace App\Controller;

use HPlus\Route\Annotation\PostApi;
use HPlus\Route\Annotation\ApiController;
use HPlus\Validate\Annotations\RequestValidation;

#[ApiController(prefix: '/api/users')]
class UserController
{
    #[PostApi(path: '')]
    #[RequestValidation(
        rules: [
            'name' => 'required|string|max:50',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
            'age' => 'nullable|integer|between:18,100',
        ],
        messages: [
            'name.required' => '用户名不能为空',
            'email.unique' => '该邮箱已被注册',
        ]
    )]
    public function create()
    {
        // 验证通过后的逻辑
        return ['message' => 'success'];
    }
}
```

### 验证模式

#### 1. JSON 模式（默认）

验证请求体（POST/PUT JSON 数据）：

```php
#[RequestValidation(
    rules: ['name' => 'required'],
    mode: 'json'  // 默认值，可省略
)]
```

#### 2. Query 模式

验证查询参数（GET 请求参数）：

```php
#[GetApi(path: '')]
#[RequestValidation(
    rules: [
        'page' => 'required|integer|min:1',
        'size' => 'required|integer|between:1,100',
        'keyword' => 'nullable|string|max:50',
    ],
    mode: 'query'
)]
public function list()
{
    // ...
}
```

#### 3. All 模式

合并验证查询参数和请求体：

```php
#[PostApi(path: '/search')]
#[RequestValidation(
    rules: [
        'page' => 'required|integer',  // 来自 query
        'filters' => 'required|array', // 来自 body
    ],
    mode: 'all'
)]
public function search()
{
    // ...
}
```

### 自定义错误消息

```php
#[RequestValidation(
    rules: [
        'email' => 'required|email',
        'password' => 'required|min:6',
    ],
    messages: [
        'email.required' => '邮箱地址不能为空',
        'email.email' => '邮箱格式不正确',
        'password.min' => '密码至少需要6个字符',
    ]
)]
```

### 自定义字段名称

```php
#[RequestValidation(
    rules: [
        'user_email' => 'required|email',
    ],
    attributes: [
        'user_email' => '用户邮箱',
    ]
)]
// 错误消息将显示："用户邮箱格式不正确"，而不是"user_email格式不正确"
```

### 停止首个失败

默认验证所有字段，返回所有错误。如果只想返回第一个错误：

```php
#[RequestValidation(
    rules: ['email' => 'required|email'],
    stopOnFirstFailure: true
)]
```

## 支持的验证规则

完全支持 Laravel Validation 所有规则，包括但不限于：

### 基础规则

- `required` - 必填
- `nullable` - 可为空
- `string` - 字符串
- `integer` - 整数
- `numeric` - 数字
- `boolean` - 布尔值
- `array` - 数组
- `json` - JSON 字符串

### 字符串规则

- `email` - 邮箱格式
- `url` - URL 格式
- `ip` - IP 地址
- `uuid` - UUID 格式
- `alpha` - 纯字母
- `alpha_num` - 字母和数字
- `alpha_dash` - 字母、数字、破折号、下划线
- `regex:pattern` - 正则表达式

### 数值规则

- `min:value` - 最小值
- `max:value` - 最大值
- `between:min,max` - 范围
- `size:value` - 大小
- `gt:field` - 大于某字段
- `gte:field` - 大于等于某字段
- `lt:field` - 小于某字段
- `lte:field` - 小于等于某字段

### 日期规则

- `date` - 日期格式
- `date_format:format` - 指定日期格式
- `before:date` - 早于某日期
- `after:date` - 晚于某日期
- `before_or_equal:date` - 早于或等于
- `after_or_equal:date` - 晚于或等于

### 数组规则

- `in:foo,bar,...` - 在指定值中
- `not_in:foo,bar,...` - 不在指定值中
- `array` - 数组类型
- `distinct` - 数组不重复

### 数据库规则

- `unique:table,column,except,idColumn` - 唯一性
- `exists:table,column` - 存在性

### 文件规则

- `file` - 文件
- `image` - 图片
- `mimes:jpg,png,...` - 文件类型
- `max:value` - 文件大小（KB）

### 关系规则

- `confirmed` - 确认字段（需要 `field_confirmation`）
- `same:field` - 与某字段相同
- `different:field` - 与某字段不同
- `required_if:field,value` - 条件必填
- `required_with:field` - 当某字段存在时必填
- `required_without:field` - 当某字段不存在时必填

更多规则请参考：https://laravel.com/docs/validation#available-validation-rules

## 高级用法

### 自定义验证规则

在 `config/autoload/dependencies.php` 中扩展验证器：

```php
use Hyperf\Validation\Contract\ValidatorFactoryInterface;
use Hyperf\Validation\ValidatorFactory;

return [
    ValidatorFactoryInterface::class => function ($container) {
        $factory = $container->get(ValidatorFactory::class);
        
        // 注册自定义规则
        $factory->extend('phone', function ($attribute, $value, $parameters, $validator) {
            return preg_match('/^1[3-9]\d{9}$/', $value);
        });
        
        // 自定义错误消息
        $factory->replacer('phone', function ($message, $attribute, $rule, $parameters) {
            return str_replace(':attribute', $attribute, ':attribute 必须是有效的手机号');
        });
        
        return $factory;
    },
];
```

使用自定义规则：

```php
#[RequestValidation(
    rules: ['mobile' => 'required|phone']
)]
```

### 嵌套数组验证

```php
#[RequestValidation(
    rules: [
        'users' => 'required|array',
        'users.*.name' => 'required|string',
        'users.*.email' => 'required|email',
        'users.*.age' => 'nullable|integer|min:18',
    ]
)]
```

<<<<<<< HEAD
### 条件验证
=======
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
>>>>>>> 6490b4a99ecb2dc9d88003e0d659cdcb6a6dc610

```php
#[RequestValidation(
    rules: [
        'type' => 'required|in:person,company',
        'id_card' => 'required_if:type,person|size:18',
        'business_license' => 'required_if:type,company',
    ]
)]
```

## 性能优化

### 规则缓存

验证规则会在首次请求时解析并缓存在内存中，后续请求直接使用缓存，无需重复解析注解。

### 查看缓存统计

```php
use HPlus\Validate\Aspect\ValidationAspect;

$stats = ValidationAspect::getCacheStats();
// [
//     'hits' => 1000,
//     'misses' => 10,
//     'total' => 1010,
//     'hit_rate' => '99.01%',
//     'rule_cache_size' => 10,
// ]
```

### 清空缓存

```php
ValidationAspect::clearCache();
```

## 错误处理

验证失败会抛出 `HPlus\Validate\Exception\ValidateException` 异常，状态码为 422。

建议在全局异常处理器中统一处理：

```php
<?php

namespace App\Exception\Handler;

use HPlus\Validate\Exception\ValidateException;
use Hyperf\ExceptionHandler\ExceptionHandler;
use Psr\Http\Message\ResponseInterface;
use Throwable;

class ValidationExceptionHandler extends ExceptionHandler
{
    public function handle(Throwable $throwable, ResponseInterface $response)
    {
        if ($throwable instanceof ValidateException) {
            return $response
                ->withStatus(422)
                ->withHeader('Content-Type', 'application/json')
                ->withBody(new SwooleStream(json_encode([
                    'code' => 422,
                    'message' => $throwable->getMessage(),
                ], JSON_UNESCAPED_UNICODE)));
        }
        
        return $response;
    }

    public function isValid(Throwable $throwable): bool
    {
        return $throwable instanceof ValidateException;
    }
}
```

<<<<<<< HEAD
## RuleParser（供 Swagger 使用）

`RuleParser` 类用于将验证规则转换为 JSON Schema，主要供 `hyperf-plus/swagger` 插件使用：

```php
use HPlus\Validate\RuleParser;

// 单个规则转换
$schema = RuleParser::ruleToJsonSchema('required|string|max:50|email');
// ['type' => 'string', 'maxLength' => 50, 'format' => 'email']

// 批量规则转换
$schema = RuleParser::rulesToJsonSchema([
    'name|姓名' => 'required|string|max:50',
    'age|年龄' => 'nullable|integer|between:18,100',
]);
// 返回完整的 JSON Schema
```

## 与旧版本的区别

### 旧版（已弃用）

```php
#[RequestValidation(
    rules: ['email' => 'required|email'],
    validate: UserValidator::class,  // ❌ 不再需要
    scene: 'create',                 // ❌ 不再需要
    filter: true,                    // ✅ 保留
    security: true,                  // ✅ 保留
    batch: true,                     // ✅ 改为 stopOnFirstFailure
    dateType: 'json'                 // ✅ 改为 mode
)]
```

### 新版（推荐）

```php
#[RequestValidation(
    rules: ['email' => 'required|email'],
    messages: [],                    // ✅ 自定义消息
    attributes: [],                  // ✅ 字段别名
    mode: 'json',                    // ✅ 验证模式
    filter: false,                   // ✅ 过滤多余字段
    security: false,                 // ✅ 安全模式
    stopOnFirstFailure: false        // ✅ 停止策略
)]
```

### 参数说明
=======
## 📝 最佳实践
>>>>>>> 6490b4a99ecb2dc9d88003e0d659cdcb6a6dc610

| 参数 | 类型 | 默认值 | 说明 |
|------|------|--------|------|
| `rules` | array | `[]` | 验证规则 (Laravel validation 规则) |
| `messages` | array | `[]` | 自定义错误消息 |
| `attributes` | array | `[]` | 字段别名（用于错误消息） |
| `mode` | string | `'json'` | 验证模式：`json`(请求体) / `query`(查询参数) / `all`(合并) |
| `filter` | bool | `false` | 是否过滤多余字段（只保留规则中定义的字段） |
| `security` | bool | `false` | 安全模式（请求中有未定义字段时抛出异常） |
| `stopOnFirstFailure` | bool | `false` | 是否在第一个失败时停止验证 |

## 迁移指南

如果你正在从旧版本迁移：

1. ✅ 保留 `rules` 参数
2. ❌ 移除 `validate` 和 `scene` 参数（改用内联规则）
3. ✅ 保留 `filter` 和 `security` 参数
4. ✅ 将 `dateType` 改为 `mode`
5. ✅ 将 `batch: false` 改为 `stopOnFirstFailure: true`

## 常见问题

### 1. 验证不生效？

检查是否正确安装了 `hyperf/validation` 和 `hyperf/translation`。

### 2. 错误消息是英文？

确保配置了中文语言包，参考"配置"部分。

### 3. 如何验证 GET 请求参数？

使用 `mode: 'query'`。

### 4. 如何同时验证 query 和 body？

使用 `mode: 'all'`。

## License

Apache-2.0