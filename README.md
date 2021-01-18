# validate
兼容tp验证器规则，支持多场景，swagger自动提取验证规则。
### 安装
```shell
composer require hyperf-plus/validate
```
验证失败抛出 ValidateException

### 用法1
```php
@RequestValidation(rules={
    "username|用户名":"require|max:25",
    "password|密码":"require"
})
```
### 用法2  验证类、验证场景
```php
@RequestValidation(validate=User::class,scene="login")
```