<?php

declare(strict_types=1);

namespace HPlus\Validate;

/**
 * 验证插件配置提供者
 * 基于 hyperf/validation 的路由验证适配器
 */
class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => [
                // RuleParser 用于 Swagger 文档生成
                RuleParser::class => RuleParser::class,
            ],
            'annotations' => [
                'scan' => [
                    'paths' => [
                        __DIR__,
                    ],
                ],
            ],
            'aspects' => [
                // 注册验证切面
                Aspect\ValidationAspect::class,
            ],
            'publish' => [
                // 发布语言包（用于 hyperf/validation）
                [
                    'id' => 'validation',
                    'description' => 'validation language files',
                    'source' => __DIR__ . '/../publish/validation.php',
                    'destination' => BASE_PATH . '/config/autoload/validation.php',
                ],
            ],
        ];
    }
}