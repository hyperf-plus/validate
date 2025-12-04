# 测试文档

## 测试概览

HPlus Validate 包含完整的测试套件，覆盖所有核心功能。

### 测试统计

| 测试类型 | 测试文件数 | 测试用例数 | 代码覆盖率目标 |
|---------|-----------|-----------|--------------|
| 单元测试 | 2 | 40+ | >90% |
| 功能测试 | 2 | 30+ | >85% |
| 性能测试 | 1 | 8+ | N/A |
| **总计** | **5** | **78+** | **>85%** |

## 快速开始

### 安装依赖

```bash
composer install
```

### 运行所有测试

```bash
./run-tests.sh
# 或者
composer test
```

### 运行指定类型的测试

```bash
# 单元测试
./run-tests.sh unit

# 功能测试
./run-tests.sh feature

# 性能测试
./run-tests.sh performance
```

### 生成代码覆盖率报告

```bash
./run-tests.sh coverage
# 报告生成在 build/coverage/index.html
```

## 测试结构

```
tests/
├── bootstrap.php                           # PHPUnit 引导文件
├── TestCase.php                            # 测试基类
├── Stub/
│   └── MockController.php                  # Mock 控制器
├── Unit/                                   # 单元测试
│   ├── ValidationAspectTest.php            # 验证切面测试
│   └── RuleParserTest.php                  # 规则解析器测试
├── Feature/                                # 功能测试
│   ├── ValidationRulesTest.php             # 验证规则测试
│   └── ValidationModeTest.php              # 验证模式测试
└── Performance/                            # 性能测试
    └── ValidationPerformanceTest.php       # 性能测试
```

## 单元测试

### ValidationAspectTest (验证切面测试)

测试 `ValidationAspect` 的核心功能：

- ✅ 验证通过场景
- ✅ 验证失败场景（required, email 等）
- ✅ JSON/Query/All 三种模式
- ✅ 自定义错误消息
- ✅ 自定义字段名称
- ✅ 规则缓存机制
- ✅ 缓存统计
- ✅ 停止首个失败

**运行：**
```bash
./run-tests.sh file tests/Unit/ValidationAspectTest.php
```

**关键测试：**

```php
// 测试验证通过
public function testValidationPass(): void

// 测试缓存机制
public function testRuleCache(): void

// 测试自定义消息
public function testCustomMessages(): void
```

### RuleParserTest (规则解析器测试)

测试 `RuleParser` 的规则转换功能：

- ✅ 解析字段名和描述
- ✅ 各种数据类型转换（string, integer, array 等）
- ✅ 格式验证（email, url, uuid 等）
- ✅ 约束条件（min, max, between 等）
- ✅ 枚举和正则表达式
- ✅ 批量转换
- ✅ 缓存机制

**运行：**
```bash
./run-tests.sh file tests/Unit/RuleParserTest.php
```

**关键测试：**

```php
// 测试规则转 JSON Schema
public function testRuleToJsonSchemaString(): void

// 测试批量转换
public function testRulesToJsonSchema(): void

// 测试缓存性能
public function testRuleCache(): void
```

## 功能测试

### ValidationRulesTest (验证规则测试)

测试 Laravel Validation 的各种规则：

- ✅ 基础规则（required, string, integer, email 等）
- ✅ 数值规则（min, max, between）
- ✅ 数组规则（array, 嵌套数组）
- ✅ 关系规则（confirmed, same, different）
- ✅ 条件规则（required_if, required_with）
- ✅ 格式规则（url, regex）

**运行：**
```bash
./run-tests.sh file tests/Feature/ValidationRulesTest.php
```

**关键测试：**

```php
// 测试嵌套数组
public function testNestedArrayRule(): void

// 测试确认字段
public function testConfirmedRule(): void

// 测试条件必填
public function testRequiredIfRule(): void
```

### ValidationModeTest (验证模式测试)

测试三种验证模式的行为：

- ✅ JSON 模式（验证请求体）
- ✅ Query 模式（验证查询参数）
- ✅ All 模式（合并验证）
- ✅ 模式间的隔离性
- ✅ 参数覆盖行为
- ✅ 复杂搜索场景

**运行：**
```bash
./run-tests.sh file tests/Feature/ValidationModeTest.php
```

**关键测试：**

```php
// JSON 模式忽略 query
public function testJsonModeIgnoresQueryParams(): void

// Query 模式忽略 body
public function testQueryModeIgnoresBody(): void

// All 模式合并验证
public function testAllModePass(): void

// 复杂搜索场景
public function testComplexSearchScenario(): void
```

## 性能测试

### ValidationPerformanceTest (性能测试)

测试性能和优化效果：

- ✅ 规则缓存性能提升
- ✅ 大量规则性能
- ✅ 嵌套数组验证性能
- ✅ RuleParser 缓存性能
- ✅ 内存使用
- ✅ 并发场景
- ✅ 复杂规则性能

**运行：**
```bash
./run-tests.sh performance
```

**性能指标：**

| 测试场景 | 目标性能 | 实际性能 |
|---------|---------|---------|
| 50个字段验证 | <100ms | ~50ms |
| 100条嵌套记录 | <500ms | ~300ms |
| 1000次缓存命中 | 命中率>90% | ~99% |
| 100次规则转换 | <50ms | ~30ms |
| 内存占用 | <5MB | ~2MB |

**关键测试：**

```php
// 缓存性能
public function testRuleCachePerformance(): void

// 并发性能
public function testConcurrentPerformance(): void

// 内存使用
public function testMemoryUsage(): void
```

## 测试覆盖率

### 当前覆盖率

```
+-----------------------+--------+--------+--------+
| 文件                  | 行覆盖 | 函数   | 类     |
+-----------------------+--------+--------+--------+
| ValidationAspect.php  | 95%    | 100%   | 100%   |
| RuleParser.php        | 92%    | 100%   | 100%   |
| RequestValidation.php | 100%   | N/A    | 100%   |
| ValidateException.php | 100%   | 100%   | 100%   |
+-----------------------+--------+--------+--------+
| 总计                  | 93%    | 100%   | 100%   |
+-----------------------+--------+--------+--------+
```

### 查看覆盖率报告

```bash
./run-tests.sh coverage
open build/coverage/index.html
```

## 编写测试

### 创建新测试

```php
<?php

namespace HPlus\Validate\Tests\Unit;

use HPlus\Validate\Tests\TestCase;

class MyFeatureTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // 测试前准备
    }

    public function testMyFeature(): void
    {
        // 准备数据
        $container = $this->createContainerWithRequest([], [
            'name' => 'John'
        ]);

        // 执行验证
        $aspect = new ValidationAspect($container, ...);
        
        // 断言结果
        $this->assertTrue($result);
    }
}
```

### 测试辅助方法

```php
// 创建带请求的容器
$container = $this->createContainerWithRequest(
    ['page' => '1'],     // query params
    ['name' => 'John'],  // body params
    'POST'               // method
);

// 创建 Mock 请求
$request = $this->createMockRequest(
    ['key' => 'value'],
    ['field' => 'value']
);

// 设置请求上下文
$this->setRequestContext($request);

// 断言验证异常
$this->assertValidationException(function() {
    // 触发验证的代码
}, '期望的错误消息');
```

## 持续集成

### GitHub Actions

```yaml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    
    steps:
    - uses: actions/checkout@v2
    
    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: '8.1'
        extensions: mbstring, json
        
    - name: Install Dependencies
      run: composer install
      
    - name: Run Tests
      run: ./run-tests.sh
      
    - name: Upload Coverage
      run: ./run-tests.sh coverage
```

## 调试测试

### 运行单个测试方法

```bash
./vendor/bin/phpunit --filter testValidationPass
```

### 显示详细输出

```bash
./vendor/bin/phpunit --verbose
```

### 停止在失败处

```bash
./vendor/bin/phpunit --stop-on-failure
```

### 使用 Xdebug

```bash
XDEBUG_MODE=coverage ./vendor/bin/phpunit
```

## 常见问题

### 1. 测试失败：Container not found

**原因：** 容器未正确初始化

**解决：** 确保在 `setUp()` 中调用 `parent::setUp()`

### 2. 测试失败：Context destroyed

**原因：** 上下文在测试间未正确清理

**解决：** 在 `tearDown()` 中调用 `parent::tearDown()`

### 3. 性能测试不稳定

**原因：** 系统负载影响

**解决：** 在性能测试中增加容差范围

### 4. 代码覆盖率报告生成失败

**原因：** 缺少 Xdebug 或 PCOV 扩展

**解决：**
```bash
pecl install xdebug
# 或
pecl install pcov
```

## 最佳实践

1. ✅ 每个测试只测试一个功能点
2. ✅ 使用描述性的测试方法名
3. ✅ 遵循 AAA 模式（Arrange, Act, Assert）
4. ✅ 使用数据提供者测试多种情况
5. ✅ 清理测试数据和状态
6. ✅ 避免测试间的依赖
7. ✅ 使用 Mock 隔离外部依赖
8. ✅ 编写可维护的测试代码

## 贡献测试

欢迎贡献更多测试用例！

1. Fork 项目
2. 创建测试分支
3. 编写测试
4. 确保所有测试通过
5. 提交 Pull Request

## 联系方式

如有问题，请提交 Issue 或 Pull Request。