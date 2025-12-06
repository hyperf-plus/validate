# 🚀 HPlus Validate v4.0.0-beta

> ⚠️ **Beta 版本**：包含破坏性变更，不建议生产环境直接升级

## 📦 安装

```bash
composer require hyperf-plus/validate:^4.0@beta
```

## ✨ 核心新特性

- 🚀 **Hyperf 原生验证** - 完全基于 `hyperf/validation`，兼容所有 Laravel 规则
- 📝 **双模式支持** - 内联规则 + FormRequest 验证器
- ⚡ **极致性能** - 多层缓存，配置/类检查/字段列表全缓存
- 🎯 **Query/Body 分离** - 清晰区分 URL 参数和请求体验证
- 🔧 **精简设计** - 代码量减少 40%

## ⚠️ 破坏性变更

1. **移除 ThinkPHP 风格 Validate**
   ```diff
   - class UserValidate extends Validate
   + class UserRequest extends FormRequest
   ```

2. **参数名称变更**：`dateType` → `mode`
   ```diff
   - #[RequestValidation(dateType: 'json')]
   + #[RequestValidation(mode: 'json')]
   ```

3. **版本要求**：PHP 8.1+, Hyperf 3.1+

## 🔄 快速迁移

```bash
# 1. 升级依赖
composer require hyperf-plus/validate:^4.0@beta

# 2. 迁移 Validate 类
# 继承 Validate → 继承 FormRequest

# 3. 更新注解参数
# dateType → mode
```

### 迁移示例

```php
// 旧代码（3.x）
class UserValidate extends Validate {
    protected $rule = ['name' => 'required'];
}

// 新代码（4.0）
class UserRequest extends FormRequest {
    public function rules(): array {
        return ['name' => 'required'];
    }
}
```

## 📝 详细文档

- [使用文档](README.md)
- [Hyperf Validation 文档](https://hyperf.wiki/3.1/#/zh-cn/validation)
- [GitHub Issues](https://github.com/hyperf-plus/validate/issues)

---

**注意**: Beta 版本可能存在未知问题，欢迎反馈！
