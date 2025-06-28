# HPlus 验证器测试指南

## 🧪 测试概述

本测试套件用于验证 HPlus\Validate 组件的功能正确性和性能表现。测试覆盖了验证器的所有核心功能，包括基础验证、复杂规则、性能优化等。

## 🚀 快速运行测试

### 方式一：使用自定义测试脚本（推荐）

```bash
# 运行所有测试
php run_tests.php
```

### 方式二：使用 Composer 脚本

```bash
# 运行测试
composer test

# 如果安装了 PHPUnit，可以运行单元测试
composer test-unit

# 生成代码覆盖率报告
composer test-coverage
```

## 📋 测试覆盖范围

### 1. 基础验证测试
- ✅ 必填字段验证 (`require`)
- ✅ 数据类型验证 (`string`, `integer`, `array`)
- ✅ 邮箱格式验证 (`email`)
- ✅ 长度验证 (`min`, `max`, `length`)

### 2. 数值范围验证
- ✅ 大于/小于验证 (`gt`, `lt`, `egt`, `elt`)
- ✅ 区间验证 (`between`)
- ✅ 枚举验证 (`in`)

### 3. 数组验证
- ✅ 数组类型验证
- ✅ 数组元素验证 (`tags.*`)
- ✅ 嵌套对象验证 (`user.profile.name`)

### 4. 高级功能测试
- ✅ 自定义错误消息
- ✅ 批量验证 (`batch`)
- ✅ 正则表达式验证 (`regex`)
- ✅ 场景验证 (`scene`)

### 5. 性能测试
- ✅ 验证性能基准测试
- ✅ 内存使用测试
- ✅ 缓存优化测试

## 🔧 测试环境要求

### 系统要求
- PHP >= 8.1
- 命令行模式 (CLI)
- 内存限制 >= 128MB

### 依赖包
```json
{
  "require-dev": {
    "phpunit/phpunit": "^10.0",
    "mockery/mockery": "^1.5"
  }
}
```

## 📊 测试结果示例

```
=== HPlus 验证器功能测试 ===

--- 基础验证测试 ---
✓ 基础必填验证 - 有效数据应该通过
✓ 基础必填验证 - 空值应该失败

--- 必填验证测试 ---
✓ 空字符串应该验证失败
✓ null值应该验证失败
✓ 缺少字段应该验证失败
✓ 有值应该验证通过
✓ 字符串0应该验证通过
✓ 数字0应该验证通过

--- 性能测试 ---
性能测试结果:
- 迭代次数: 1000
- 总时间: 45.32ms
- 平均时间: 0.0453ms/次
- 内存使用: 2.15 MB
✓ 1000次验证应该在1秒内完成
✓ 单次验证应该在1ms内完成

=== 测试结果 ===
通过: 48
失败: 0
总计: 48

🎉 所有测试通过！
```

## 🐛 故障排除

### 常见问题

1. **类未找到错误**
   ```
   Fatal error: Class 'HPlus\Validate\Validate' not found
   ```
   解决方案：确保正确安装了 Composer 依赖
   ```bash
   composer install
   ```

2. **PHP 版本不兼容**
   ```
   错误: 需要 PHP 8.1 或更高版本
   ```
   解决方案：升级 PHP 版本到 8.1 或更高

3. **内存不足**
   ```
   Fatal error: Allowed memory size exhausted
   ```
   解决方案：增加 PHP 内存限制
   ```bash
   php -d memory_limit=512M run_tests.php
   ```

### 调试模式

如果测试失败，可以开启详细的错误输出：

```bash
# 开启所有错误报告
php -d display_errors=1 -d error_reporting=E_ALL run_tests.php
```

## 📈 性能基准

### 预期性能指标

| 测试项目 | 预期结果 | 说明 |
|---------|---------|------|
| 单次简单验证 | < 1ms | 基础字段验证 |
| 1000次验证 | < 1秒 | 批量验证性能 |
| 内存使用 | < 10MB | 大量验证的内存消耗 |
| 复杂规则验证 | < 5ms | 包含正则、嵌套验证 |

### 性能优化特性

- 🚀 **规则缓存**: 避免重复解析验证规则
- 🧠 **智能内存管理**: 及时释放不需要的对象
- ⚡ **懒加载**: 只在需要时加载验证器
- 🎯 **批量优化**: 支持批量验证模式

## 📝 添加新测试

### 创建新的测试方法

在 `tests/SimpleValidateTest.php` 中添加新的测试方法：

```php
/**
 * 测试新功能
 */
public function testNewFeature(): void
{
    echo "\n--- 新功能测试 ---\n";
    
    $validator = new Validate();
    $rules = ['field' => 'new_rule'];
    
    // 测试有效数据
    $this->assertTrue(
        $validator->check(['field' => 'valid_value'], $rules), 
        '有效数据应该验证通过'
    );
    
    // 测试无效数据
    $this->assertFalse(
        $validator->check(['field' => 'invalid_value'], $rules), 
        '无效数据应该验证失败'
    );
}
```

然后在 `runTests()` 方法中调用：

```php
public function runTests(): void
{
    // 现有测试...
    $this->testNewFeature();
    // ...
}
```

## 🎯 测试最佳实践

1. **测试隔离**: 每个测试方法独立，不依赖其他测试
2. **明确断言**: 使用清晰的断言消息说明预期结果
3. **边界测试**: 测试边界条件和异常情况
4. **性能测试**: 包含性能基准，确保性能不会回退
5. **错误测试**: 验证错误处理逻辑的正确性

## 📚 相关文档

- [验证器使用说明](README.md)
- [API 文档](docs/API.md)
- [性能优化指南](docs/PERFORMANCE_OPTIMIZATION.md)
- [常见问题](docs/FAQ.md) 