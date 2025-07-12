<?php

require_once __DIR__ . '/../vendor/autoload.php';

use HPlus\Validate\Validate;

// 测试 nullable 可空字段验证
echo "=== 测试 nullable 可空字段验证 ===\n";
$validator = Validate::make([
    'name' => 'required|string',
    'email' => 'nullable|email',
    'age' => 'nullable|integer|min:0'
]);

// 测试数据1: email 为空但可空
$data1 = [
    'name' => 'John Doe',
    'email' => null,
    'age' => null
];

$result1 = $validator->check($data1);
echo "数据1 验证结果: " . ($result1 ? "通过" : "失败") . "\n";
if (!$result1) {
    echo "错误信息: " . json_encode($validator->getError()) . "\n";
}

// 测试数据2: email 有值但格式错误
$data2 = [
    'name' => 'John Doe',
    'email' => 'invalid-email',
    'age' => 25
];

$result2 = $validator->check($data2);
echo "数据2 验证结果: " . ($result2 ? "通过" : "失败") . "\n";
if (!$result2) {
    echo "错误信息: " . json_encode($validator->getError()) . "\n";
}

echo "\n=== 测试 default 默认值设置 ===\n";
$validator2 = Validate::make([
    'status' => 'default:active|string',
    'page' => 'default:1|integer|min:1',
    'limit' => 'default:20|integer|min:1|max:100',
    'is_active' => 'default:true|boolean'
]);

// 测试数据: 未提供字段，应使用默认值
$data3 = [];
$result3 = $validator2->check($data3);
echo "数据3 验证结果: " . ($result3 ? "通过" : "失败") . "\n";
if (!$result3) {
    echo "错误信息: " . json_encode($validator2->getError()) . "\n";
}

echo "\n=== 测试 after_or_equal 日期验证 ===\n";
$validator3 = Validate::make([
    'start_date' => 'required|date',
    'end_date' => 'required|date|afterOrEqual:2024-01-01'
]);

// 测试数据: 结束日期在指定日期之后
$data4 = [
    'start_date' => '2024-06-01',
    'end_date' => '2024-12-31'
];

$result4 = $validator3->check($data4);
echo "数据4 验证结果: " . ($result4 ? "通过" : "失败") . "\n";
if (!$result4) {
    echo "错误信息: " . json_encode($validator3->getError()) . "\n";
}

// 测试数据: 结束日期在指定日期之前
$data5 = [
    'start_date' => '2024-06-01',
    'end_date' => '2023-12-31'
];

$result5 = $validator3->check($data5);
echo "数据5 验证结果: " . ($result5 ? "通过" : "失败") . "\n";
if (!$result5) {
    echo "错误信息: " . json_encode($validator3->getError()) . "\n";
}

echo "\n=== 测试数组元素验证 ===\n";
$validator4 = Validate::make([
    'items' => 'required|array',
    'items.*' => 'required|string|max:50',
    'users.*.name' => 'required|string|max:100',
    'users.*.email' => 'required|email'
]);

// 测试数据: 数组元素验证
$data6 = [
    'items' => ['item1', 'item2', 'item3'],
    'users' => [
        ['name' => 'John Doe', 'email' => 'john@example.com'],
        ['name' => 'Jane Smith', 'email' => 'jane@example.com']
    ]
];

$result6 = $validator4->check($data6);
echo "数据6 验证结果: " . ($result6 ? "通过" : "失败") . "\n";
if (!$result6) {
    echo "错误信息: " . json_encode($validator4->getError()) . "\n";
}

// 测试数据: 数组元素验证失败
$data7 = [
    'items' => ['item1', '', 'item3'], // 第二个元素为空
    'users' => [
        ['name' => 'John Doe', 'email' => 'john@example.com'],
        ['name' => 'Jane Smith', 'email' => 'invalid-email'] // 邮箱格式错误
    ]
];

$result7 = $validator4->check($data7);
echo "数据7 验证结果: " . ($result7 ? "通过" : "失败") . "\n";
if (!$result7) {
    echo "错误信息: " . json_encode($validator4->getError()) . "\n";
}

echo "\n=== 测试组合验证规则 ===\n";
$validator5 = Validate::make([
    'name' => 'required|string|max:100',
    'email' => 'nullable|email',
    'age' => 'default:18|integer|min:0|max:150',
    'created_at' => 'default:2024-01-01|date|afterOrEqual:2024-01-01',
    'tags.*' => 'nullable|string|max:50'
]);

// 测试数据: 组合验证
$data8 = [
    'name' => 'Test User',
    'email' => null,
    'tags' => ['tag1', 'tag2', null, 'tag3']
];

$result8 = $validator5->check($data8);
echo "数据8 验证结果: " . ($result8 ? "通过" : "失败") . "\n";
if (!$result8) {
    echo "错误信息: " . json_encode($validator5->getError()) . "\n";
}

echo "\n=== 测试完成 ===\n"; 