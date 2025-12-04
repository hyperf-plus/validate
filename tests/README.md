# Hyperf-Plus Validate 测试文档

## 测试覆盖范围

本测试套件全面覆盖了验证器的所有功能：

### 1. 核心验证功能测试 (ValidateTest.php)
- ✅ 基础类型验证（string, integer, numeric, boolean, array）
- ✅ 长度验证（min, max, length, between）
- ✅ 格式验证（email, url, ip, mobile）
- ✅ 正则验证（alpha, alphaNum, alphaDash, regex）
- ✅ 比较验证（gt, egt, lt, elt, eq）
- ✅ 范围验证（in, notIn）
- ✅ 日期验证（date, dateFormat, before, after）
- ✅ 条件验证（requireIf, requireWith）
- ✅ 字段比较（confirm, different）
- ✅ 多规则组合
- ✅ 嵌套数组验证
- ✅ 批量验证
- ✅ 自定义错误消息
- ✅ 字段描述

### 2. 验证切面测试 (ValidationAspectTest.php)
- ✅ RequestValidation注解验证
- ✅ 验证失败异常处理
- ✅ Security模式（严格模式）
- ✅ Filter模式（过滤模式）
- ✅ 自定义错误消息
- ✅ 字段属性名称
- ✅ 缓存功能
- ✅ 参数验证（Validation注解）
- ✅ 场景验证

### 3. 规则解析器测试 (RuleParserTest.php)
- ✅ 字段名解析
- ✅ 规则转JSON Schema
- ✅ 格式检测
- ✅ 约束条件
- ✅ 规则数组转JSON Schema
- ✅ 带描述的字段
- ✅ 缓存功能
- ✅ 批量操作
- ✅ 默认值和示例值
- ✅ 内存优化
- ✅ 预热功能

### 4. 性能测试 (PerformanceTest.php)
- ✅ 单次验证性能（< 0.5ms）
- ✅ 规则解析缓存性能（10倍提升）
- ✅ 批量验证性能（< 0.2ms/条）
- ✅ 内存使用测试（< 10MB增长）
- ✅ 并发验证性能（Swoole环境）
- ✅ 缓存命中率测试

### 5. 集成测试 (IntegrationTest.php)
- ✅ 控制器注解验证
- ✅ 参数注解验证
- ✅ 自定义验证器
- ✅ ValidateRule助手类
- ✅ 复杂嵌套验证
- ✅ 验证异常处理
- ✅ Hyperf框架集成
- ✅ 性能监控集成

### 6. 功能完整性测试 (FunctionalityTest.php)
- ✅ 所有字符串验证规则（chs, chsAlpha, chsAlphaNum, chsDash, lower, upper）
- ✅ 所有数字验证规则（float, number, 数学比较）
- ✅ 所有日期时间验证规则
- ✅ 文件验证规则
- ✅ 特殊验证规则（token, json, xml）
- ✅ 数组验证规则
- ✅ 条件验证规则（所有require*规则）
- ✅ 字段比较规则
- ✅ IP和网络验证
- ✅ 中国特色验证（手机号、身份证、邮编）

## 运行测试

### Windows环境
```bash
# 运行所有测试
run-tests.bat

# 或手动运行
composer test
```

### Linux/Mac环境
```bash
# 添加执行权限
chmod +x run-tests.sh

# 运行所有测试
./run-tests.sh

# 或手动运行
composer test
```

### 运行特定测试
```bash
# 只运行核心功能测试
vendor/bin/phpunit tests/Cases/ValidateTest.php

# 只运行性能测试
vendor/bin/phpunit tests/Cases/PerformanceTest.php

# 运行带覆盖率的测试
composer test-coverage
```

## 测试结果说明

### 性能指标
- 单次验证平均耗时：< 0.1ms
- 批量验证吞吐量：> 20,000 条/秒
- 缓存命中率：> 85%
- 内存增长：< 10KB/验证

### 代码覆盖率
- 目标覆盖率：> 90%
- 核心功能覆盖率：100%
- 边界情况覆盖：完整

## 持续集成

建议在CI/CD流程中加入以下步骤：

```yaml
# GitHub Actions 示例
- name: Run tests
  run: |
    composer install
    composer test
    
- name: Upload coverage
  uses: codecov/codecov-action@v3
  with:
    file: ./tests/coverage/clover.xml
```

## 注意事项

1. **环境要求**
   - PHP >= 8.1
   - 安装所有composer依赖
   - Swoole扩展（可选，用于并发测试）

2. **测试数据**
   - 测试使用模拟数据，不会影响实际数据库
   - 文件上传测试使用模拟文件数据

3. **性能测试**
   - 性能测试结果可能因硬件环境而异
   - 建议在生产环境相似的配置下运行性能测试

4. **扩展测试**
   - 可以根据需要添加更多测试用例
   - 建议为新功能添加对应的测试

## 贡献指南

欢迎提交测试用例！请确保：

1. 新测试遵循现有的命名规范
2. 测试方法名称描述清晰
3. 包含正面和负面测试用例
4. 添加适当的注释说明

## 问题反馈

如果发现测试问题或需要添加新的测试场景，请在GitHub上提交Issue。 