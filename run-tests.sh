#!/bin/bash

# 颜色定义
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}  HPlus Validate 测试套件${NC}"
echo -e "${GREEN}========================================${NC}"
echo ""

# 检查 vendor 目录
if [ ! -d "vendor" ]; then
    echo -e "${YELLOW}正在安装依赖...${NC}"
    composer install
fi

# 运行所有测试
if [ "$1" == "" ]; then
    echo -e "${YELLOW}运行所有测试...${NC}"
    ./vendor/bin/phpunit --colors=always

# 运行单元测试
elif [ "$1" == "unit" ]; then
    echo -e "${YELLOW}运行单元测试...${NC}"
    ./vendor/bin/phpunit --colors=always --testsuite "Unit Tests"

# 运行功能测试
elif [ "$1" == "feature" ]; then
    echo -e "${YELLOW}运行功能测试...${NC}"
    ./vendor/bin/phpunit --colors=always --testsuite "Feature Tests"

# 运行性能测试
elif [ "$1" == "performance" ]; then
    echo -e "${YELLOW}运行性能测试...${NC}"
    ./vendor/bin/phpunit --colors=always --testsuite "Performance Tests"

# 运行代码覆盖率测试
elif [ "$1" == "coverage" ]; then
    echo -e "${YELLOW}运行代码覆盖率测试...${NC}"
    ./vendor/bin/phpunit --colors=always --coverage-html build/coverage

# 运行指定的测试文件
elif [ "$1" == "file" ]; then
    if [ "$2" == "" ]; then
        echo -e "${RED}错误: 请指定测试文件${NC}"
        echo "使用方式: ./run-tests.sh file tests/Unit/ValidationAspectTest.php"
        exit 1
    fi
    echo -e "${YELLOW}运行测试文件: $2${NC}"
    ./vendor/bin/phpunit --colors=always "$2"

# 显示帮助信息
elif [ "$1" == "help" ]; then
    echo "使用方式: ./run-tests.sh [选项]"
    echo ""
    echo "选项:"
    echo "  (无)          - 运行所有测试"
    echo "  unit          - 仅运行单元测试"
    echo "  feature       - 仅运行功能测试"
    echo "  performance   - 仅运行性能测试"
    echo "  coverage      - 运行代码覆盖率测试"
    echo "  file <路径>   - 运行指定的测试文件"
    echo "  help          - 显示此帮助信息"
    echo ""
    echo "示例:"
    echo "  ./run-tests.sh"
    echo "  ./run-tests.sh unit"
    echo "  ./run-tests.sh file tests/Unit/ValidationAspectTest.php"
    exit 0

else
    echo -e "${RED}错误: 未知选项 '$1'${NC}"
    echo "使用 './run-tests.sh help' 查看帮助"
    exit 1
fi

# 检查测试结果
if [ $? -eq 0 ]; then
    echo ""
    echo -e "${GREEN}✓ 所有测试通过！${NC}"
    exit 0
else
    echo ""
    echo -e "${RED}✗ 测试失败${NC}"
    exit 1
fi
