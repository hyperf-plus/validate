<?php

require_once __DIR__ . '/../vendor/autoload.php';

use HPlus\Validate\Validate;

echo "=== 测试 same 字段比较验证 ===\n";

// 测试1: 密码确认验证
$validator1 = Validate::make([
    'password' => 'required|string|min:6',
    'password_confirmation' => 'required|same:password'
]);

$data1 = [
    'password' => '123456',
    'password_confirmation' => '123456'
];

$result1 = $validator1->check($data1);
echo "测试1 (密码相同): " . ($result1 ? "通过" : "失败") . "\n";
if (!$result1) {
    echo "错误信息: " . json_encode($validator1->getError()) . "\n";
}

// 测试2: 密码不一致
$data2 = [
    'password' => '123456',
    'password_confirmation' => '654321'
];

$result2 = $validator1->check($data2);
echo "测试2 (密码不同): " . ($result2 ? "通过" : "失败") . "\n";
if (!$result2) {
    echo "错误信息: " . json_encode($validator1->getError()) . "\n";
}

// 测试3: 邮箱确认验证
$validator2 = Validate::make([
    'email' => 'required|email',
    'email_confirmation' => 'required|same:email'
]);

$data3 = [
    'email' => 'test@example.com',
    'email_confirmation' => 'test@example.com'
];

$result3 = $validator2->check($data3);
echo "测试3 (邮箱相同): " . ($result3 ? "通过" : "失败") . "\n";
if (!$result3) {
    echo "错误信息: " . json_encode($validator2->getError()) . "\n";
}

// 测试4: 邮箱不一致
$data4 = [
    'email' => 'test@example.com',
    'email_confirmation' => 'different@example.com'
];

$result4 = $validator2->check($data4);
echo "测试4 (邮箱不同): " . ($result4 ? "通过" : "失败") . "\n";
if (!$result4) {
    echo "错误信息: " . json_encode($validator2->getError()) . "\n";
}

// 测试5: 数字字段比较
$validator3 = Validate::make([
    'amount' => 'required|numeric',
    'confirm_amount' => 'required|same:amount'
]);

$data5 = [
    'amount' => 100.50,
    'confirm_amount' => 100.50
];

$result5 = $validator3->check($data5);
echo "测试5 (金额相同): " . ($result5 ? "通过" : "失败") . "\n";
if (!$result5) {
    echo "错误信息: " . json_encode($validator3->getError()) . "\n";
}

// 测试6: 类型不同的值比较
$data6 = [
    'amount' => 100,
    'confirm_amount' => '100'
];

$result6 = $validator3->check($data6);
echo "测试6 (类型不同): " . ($result6 ? "通过" : "失败") . "\n";
if (!$result6) {
    echo "错误信息: " . json_encode($validator3->getError()) . "\n";
}

echo "\n=== same 规则测试完成 ===\n"; 