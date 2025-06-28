@echo off
echo ===================================
echo Hyperf-Plus Validate 测试套件
echo ===================================
echo.

echo 正在安装测试依赖...
composer install --dev

echo.
echo 运行单元测试...
echo ===================================
vendor\bin\phpunit -c phpunit.xml --colors=always

echo.
echo 生成代码覆盖率报告...
echo ===================================
vendor\bin\phpunit -c phpunit.xml --colors=always --coverage-html tests/coverage --coverage-text

echo.
echo 测试完成！
echo 覆盖率报告已生成在: tests/coverage/index.html
pause 